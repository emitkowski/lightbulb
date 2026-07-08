<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestLinkedInJobsSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestLinkedInJobsSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.linkedin.actor_id', 'curious_coder/linkedin-jobs-scraper');
        Config::set('ingestion.apify.linkedin.max_age_days', 14);
    }

    private function runJob(string $query): void
    {
        (new IngestLinkedInJobsSignalsJob($query))->handle(new IngestionService(), new ApifyService());
    }

    private function makePosting(array $overrides = []): array
    {
        return array_merge([
            'id' => 'li-321',
            'title' => 'SaaS Operations Manager',
            'descriptionText' => 'Own our workflow automation stack and manually track content ops until we find a tool.',
            'link' => 'https://www.linkedin.com/jobs/view/321',
            'companyName' => 'Northwind Software',
            'location' => 'Remote',
            'postedAt' => now()->subDays(2)->toIso8601String(),
        ], $overrides);
    }

    public function test_inserts_qualifying_posting_as_signal(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('saas operations');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'linkedin',
            'source_id' => 'li-321',
            'author' => 'Northwind Software',
        ]);
    }

    public function test_skips_postings_older_than_max_age(): void
    {
        Http::fakeSequence()->push([
            $this->makePosting(['postedAt' => now()->subDays(30)->toIso8601String()]),
        ], 200);

        $this->runJob('saas operations');

        $this->assertSame(0, RawSignal::where('source', 'linkedin')->count());
    }

    public function test_skips_postings_missing_url_or_title(): void
    {
        Http::fakeSequence()->push([$this->makePosting(['link' => null])], 200);

        $this->runJob('saas operations');

        $this->assertSame(0, RawSignal::where('source', 'linkedin')->count());
    }

    public function test_deduplicates_postings_with_same_id(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('saas operations');

        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('content operations');

        $this->assertSame(1, RawSignal::where('source', 'linkedin')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('saas operations');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'linkedin',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('saas operations');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'linkedin',
            'query' => 'saas operations',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
