<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestPeoplePerHourSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestPeoplePerHourSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.peopleperhour.actor_id', 'getdataforme/peopleperhour-job-scraper');
        Config::set('ingestion.apify.peopleperhour.min_budget', 200);
        Config::set('ingestion.apify.peopleperhour.max_age_days', 14);
    }

    private function runJob(string $query): void
    {
        (new IngestPeoplePerHourSignalsJob($query))->handle(new IngestionService(), new ApifyService());
    }

    private function makePosting(array $overrides = []): array
    {
        return array_merge([
            'project_id' => 'pph-123',
            'title' => 'Build a client reporting automation tool',
            'description' => 'Need someone to automate our weekly client reports.',
            'url' => 'https://www.peopleperhour.com/freelance-jobs/business/reporting-tool-123',
            'budget_converted' => 800,
            'currency' => 'GBP',
            'category' => 'business',
            'sub_category' => 'reporting',
            'location_type' => 'remote',
            'posted_date' => now()->subDays(2)->toIso8601String(),
            'proposal_count' => 5,
            'client_country' => 'United Kingdom',
        ], $overrides);
    }

    public function test_inserts_qualifying_job_postings_as_signals(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('client reporting');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'peopleperhour',
            'source_id' => 'pph-123',
        ]);
    }

    public function test_skips_postings_below_min_budget(): void
    {
        Http::fakeSequence()->push([$this->makePosting(['budget_converted' => 50])], 200);

        $this->runJob('client reporting');

        $this->assertSame(0, RawSignal::where('source', 'peopleperhour')->count());
    }

    public function test_skips_postings_older_than_max_age(): void
    {
        Http::fakeSequence()->push([
            $this->makePosting(['posted_date' => now()->subDays(30)->toIso8601String()]),
        ], 200);

        $this->runJob('client reporting');

        $this->assertSame(0, RawSignal::where('source', 'peopleperhour')->count());
    }

    public function test_deduplicates_postings_with_same_project_id(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('client reporting');

        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('custom tool');

        $this->assertSame(1, RawSignal::where('source', 'peopleperhour')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('client reporting');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'peopleperhour',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('client reporting');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'peopleperhour',
            'query' => 'client reporting',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
