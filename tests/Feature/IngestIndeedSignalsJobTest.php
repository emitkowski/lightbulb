<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestIndeedSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestIndeedSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.indeed.actor_id', 'misceres/indeed-scraper');
        Config::set('ingestion.apify.indeed.country', 'US');
        Config::set('ingestion.apify.indeed.max_age_days', 14);
    }

    private function runJob(string $query): void
    {
        (new IngestIndeedSignalsJob($query))->handle(new IngestionService(), new ApifyService());
    }

    private function makePosting(array $overrides = []): array
    {
        return array_merge([
            'id' => 'indeed-789',
            'positionName' => 'Client Operations Specialist',
            'description' => 'Responsible for manually compiling weekly client reports until we adopt a tool.',
            'url' => 'https://www.indeed.com/viewjob?jk=789',
            'company' => 'Acme Agency',
            'location' => 'Remote',
            'postingDateParsed' => now()->subDays(2)->toIso8601String(),
        ], $overrides);
    }

    public function test_inserts_qualifying_posting_as_signal(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('client reporting');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'indeed',
            'source_id' => 'indeed-789',
            'author' => 'Acme Agency',
        ]);
    }

    public function test_skips_postings_older_than_max_age(): void
    {
        Http::fakeSequence()->push([
            $this->makePosting(['postingDateParsed' => now()->subDays(30)->toIso8601String()]),
        ], 200);

        $this->runJob('client reporting');

        $this->assertSame(0, RawSignal::where('source', 'indeed')->count());
    }

    public function test_skips_postings_missing_url_or_title(): void
    {
        Http::fakeSequence()->push([$this->makePosting(['url' => null])], 200);

        $this->runJob('client reporting');

        $this->assertSame(0, RawSignal::where('source', 'indeed')->count());
    }

    public function test_deduplicates_postings_with_same_id(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('client reporting');

        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('workflow automation');

        $this->assertSame(1, RawSignal::where('source', 'indeed')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('client reporting');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'indeed',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('client reporting');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'indeed',
            'query' => 'client reporting',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
