<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestAppSumoSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestAppSumoSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.appsumo.actor_id', 'shahidirfan/appsumo-scraper');
        Config::set('ingestion.apify.appsumo.max_reviews_per_category', 100);
        Config::set('ingestion.apify.appsumo.max_star_rating', 4);
        Config::set('ingestion.apify.appsumo.min_review_count', 50);
    }

    private function runJob(string $category): void
    {
        (new IngestAppSumoSignalsJob($category))->handle(new IngestionService(), new ApifyService());
    }

    private function makeProduct(array $overrides = []): array
    {
        return array_merge([
            'title' => 'ReportBuilder Pro',
            'description' => 'Build client reports in minutes instead of hours.',
            'url' => 'https://appsumo.com/products/reportbuilder-pro',
            'avgRating' => 3.5,
            'reviewCount' => 120,
            'price' => 49,
        ], $overrides);
    }

    private function makeReview(array $overrides = []): array
    {
        return array_merge([
            'reviewBody' => 'I wish it had a Zapier integration. Missing export to PDF.',
            'rating' => 3,
            'url' => 'https://appsumo.com/products/reportbuilder-pro#reviews',
            'reviewer' => 'Bob K.',
            'productName' => 'ReportBuilder Pro',
        ], $overrides);
    }

    public function test_inserts_products_with_many_reviews_and_mediocre_rating(): void
    {
        Http::fakeSequence()->push([$this->makeProduct()], 200);

        $this->runJob('productivity-automation');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'appsumo',
            'category' => 'productivity-automation',
        ]);
    }

    public function test_skips_products_above_max_star_rating(): void
    {
        Http::fakeSequence()->push([$this->makeProduct(['avgRating' => 4.8])], 200);

        $this->runJob('productivity-automation');

        $this->assertSame(0, RawSignal::where('source', 'appsumo')->count());
    }

    public function test_skips_products_below_min_review_count(): void
    {
        Http::fakeSequence()->push([$this->makeProduct(['reviewCount' => 10])], 200);

        $this->runJob('productivity-automation');

        $this->assertSame(0, RawSignal::where('source', 'appsumo')->count());
    }

    public function test_inserts_products_using_the_actors_actual_snake_case_field_names(): void
    {
        // shahidirfan/appsumo-scraper (live-verified 2026-07-08) returns review_count/
        // description_text, not the camelCase names used elsewhere in this test file.
        $product = [
            'title' => 'BreezeDoc',
            'description_text' => 'Use this dynamic electronic signature tool.',
            'url' => 'https://appsumo.com/products/breezedoc',
            'rating' => 3.72,
            'review_count' => 162,
            'price' => 19,
        ];

        Http::fakeSequence()->push([$product], 200);

        $this->runJob('productivity-automation');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'appsumo',
            'title' => 'BreezeDoc',
            'score' => 162,
        ]);
    }

    public function test_sends_a_keyword_search_request_to_the_actor(): void
    {
        Http::fakeSequence()->push([$this->makeProduct()], 200);

        $this->runJob('productivity-automation');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'shahidirfan~appsumo-scraper')
                && ($request['keyword'] ?? null) === 'productivity automation'
                && ($request['results_wanted'] ?? null) === 100;
        });
    }

    public function test_inserts_gap_reviews_when_actor_returns_review_level_items(): void
    {
        Http::fakeSequence()->push([$this->makeReview()], 200);

        $this->runJob('productivity-automation');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'appsumo',
            'category' => 'productivity-automation',
        ]);
    }

    public function test_skips_reviews_without_gap_keywords(): void
    {
        $review = $this->makeReview(['reviewBody' => 'Excellent product, love it!', 'rating' => 3]);
        Http::fakeSequence()->push([$review], 200);

        $this->runJob('productivity-automation');

        $this->assertSame(0, RawSignal::where('source', 'appsumo')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('productivity-automation');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'appsumo',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makeProduct()], 200);

        $this->runJob('productivity-automation');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'appsumo',
            'query' => 'productivity-automation',
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
