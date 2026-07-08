<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestG2ReviewsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestG2ReviewsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.g2.actor_id', 'epctex/g2-scraper');
        Config::set('ingestion.apify.g2.max_reviews_per_category', 50);
        Config::set('ingestion.apify.g2.max_star_rating', 3);
    }

    private function runJob(string $category): void
    {
        (new IngestG2ReviewsJob($category))->handle(new IngestionService(), new ApifyService());
    }

    private function makeApifyResponse(array $items): array
    {
        return $items;
    }

    private function makeReview(array $overrides = []): array
    {
        return array_merge([
            'reviewTitle' => 'Good but missing key features',
            'reviewBody' => 'I wish it had a bulk export feature. Missing integration with Slack.',
            'rating' => 2,
            'productName' => 'TimeTool Pro',
            'url' => 'https://www.g2.com/products/timetool-pro/reviews/abc123',
            'reviewer' => 'Alice B.',
            'role' => 'Product Manager',
            'companySize' => '51-200',
        ], $overrides);
    }

    public function test_inserts_low_rating_gap_reviews_as_signals(): void
    {
        Http::fakeSequence()->push($this->makeApifyResponse([$this->makeReview()]), 200);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'g2',
            'category' => 'time-tracking',
        ]);
    }

    public function test_skips_reviews_above_max_star_rating(): void
    {
        Http::fakeSequence()->push($this->makeApifyResponse([$this->makeReview(['rating' => 5])]), 200);

        $this->runJob('time-tracking');

        $this->assertSame(0, RawSignal::where('source', 'g2')->count());
    }

    public function test_skips_reviews_without_gap_keywords(): void
    {
        $review = $this->makeReview([
            'reviewTitle' => 'Great tool',
            'reviewBody' => 'Love using this every day, highly recommend it.',
        ]);
        Http::fakeSequence()->push($this->makeApifyResponse([$review]), 200);

        $this->runJob('time-tracking');

        $this->assertSame(0, RawSignal::where('source', 'g2')->count());
    }

    public function test_deduplicates_reviews_with_same_url(): void
    {
        Http::fakeSequence()->push($this->makeApifyResponse([$this->makeReview()]), 200);
        $this->runJob('time-tracking');

        Http::fakeSequence()->push($this->makeApifyResponse([$this->makeReview()]), 200);
        $this->runJob('time-tracking');

        $this->assertSame(1, RawSignal::where('source', 'g2')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'g2',
            'status' => 'failed',
        ]);
    }

    public function test_logs_failed_run_on_apify_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'g2',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push($this->makeApifyResponse([$this->makeReview()]), 200);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'g2',
            'query' => 'time-tracking',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }

    public function test_handles_alternate_field_names_from_actor(): void
    {
        $review = [
            'title' => 'Wish it had better exports',
            'body' => 'It would be perfect if there was an API. No support for webhooks.',
            'stars' => 2,
            'product' => 'AnotherTool',
            'url' => 'https://www.g2.com/products/anothertool/reviews/xyz',
        ];
        Http::fakeSequence()->push([$review], 200);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'g2',
            'category' => 'time-tracking',
        ]);
    }
}
