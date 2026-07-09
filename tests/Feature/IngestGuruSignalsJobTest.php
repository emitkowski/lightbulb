<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestGuruSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestGuruSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.guru.actor_id', 'shahidirfan/guru-com-scraper');
        Config::set('ingestion.apify.guru.min_budget', 200);
        Config::set('ingestion.apify.guru.max_age_days', 14);
    }

    private function runJob(string $query): void
    {
        (new IngestGuruSignalsJob($query))->handle(new IngestionService(), new ApifyService());
    }

    private function makePosting(array $overrides = []): array
    {
        return array_merge([
            'id' => 'guru-456',
            'title' => 'Build an internal workflow automation tool',
            'description' => 'We need a custom tool to automate our client onboarding.',
            'url' => 'https://www.guru.com/jobs/build-workflow-automation-tool',
            'budget' => 900,
            'postedAt' => now()->subDays(2)->toIso8601String(),
            'bidCount' => 8,
            'clientCountry' => 'Canada',
        ], $overrides);
    }

    public function test_inserts_qualifying_job_postings_as_signals(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('workflow automation tool');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'guru',
            'source_id' => 'guru-456',
        ]);
    }

    public function test_inserts_postings_using_the_actors_actual_field_shape(): void
    {
        // shahidirfan/guru-com-scraper (live-verified 2026-07-08) has no id/postedAt
        // fields, and price is a free-text string like "Fixed Price | Under $250".
        $posting = [
            'title' => 'Excel Reorganize Column Data',
            'description' => 'Need columns split into address parts.',
            'url' => 'https://www.guru.com/jobs/excel-reorganize-column-data/2119330',
            'price' => 'Fixed Price | Under $250',
            'employerName' => 'Bev G',
            'location' => 'United States',
        ];

        Http::fakeSequence()->push([$posting], 200);

        $this->runJob('workflow automation tool');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'guru',
            'title' => 'Excel Reorganize Column Data',
            'score' => 250,
        ]);
    }

    public function test_sends_a_keyword_search_request_to_the_actor(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('workflow automation tool');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'shahidirfan~guru-com-scraper')
                && ($request['keyword'] ?? null) === 'workflow automation tool'
                && array_key_exists('results_wanted', $request->data());
        });
    }

    public function test_skips_postings_below_min_budget(): void
    {
        Http::fakeSequence()->push([$this->makePosting(['budget' => 50])], 200);

        $this->runJob('workflow automation tool');

        $this->assertSame(0, RawSignal::where('source', 'guru')->count());
    }

    public function test_skips_postings_older_than_max_age(): void
    {
        Http::fakeSequence()->push([
            $this->makePosting(['postedAt' => now()->subDays(30)->toIso8601String()]),
        ], 200);

        $this->runJob('workflow automation tool');

        $this->assertSame(0, RawSignal::where('source', 'guru')->count());
    }

    public function test_handles_budget_as_string_range(): void
    {
        Http::fakeSequence()->push([$this->makePosting(['budget' => '500-2000'])], 200);

        $this->runJob('workflow automation tool');

        $this->assertDatabaseHas('raw_signals', ['source' => 'guru', 'source_id' => 'guru-456']);
    }

    public function test_deduplicates_postings_with_same_id(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('workflow automation tool');

        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('custom tool');

        $this->assertSame(1, RawSignal::where('source', 'guru')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('workflow automation tool');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'guru',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('workflow automation tool');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'guru',
            'query' => 'workflow automation tool',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
