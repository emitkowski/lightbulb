<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestFreelancePlatformsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestFreelancePlatformsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.freelance.actor_id', 'epctex/upwork-scraper');
        Config::set('ingestion.apify.freelance.min_budget', 500);
        Config::set('ingestion.apify.freelance.max_age_days', 7);
    }

    private function runJob(string $query): void
    {
        (new IngestFreelancePlatformsJob($query))->handle(new IngestionService(), new ApifyService());
    }

    private function makePosting(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Build a custom client reporting dashboard',
            'description' => 'We need someone to build an internal tool that automates our monthly reporting.',
            'url' => 'https://www.upwork.com/jobs/Build-custom-dashboard_~01abc123',
            'budget' => 1500,
            'postedAt' => now()->subDays(2)->toIso8601String(),
            'proposalCount' => 12,
            'skills' => ['Laravel', 'Vue.js', 'MySQL'],
            'clientCountry' => 'United States',
        ], $overrides);
    }

    public function test_inserts_qualifying_job_postings_as_signals(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('build a custom dashboard');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'freelance',
            'category' => 'build a custom dashboard',
        ]);
    }

    public function test_skips_postings_below_min_budget(): void
    {
        Http::fakeSequence()->push([$this->makePosting(['budget' => 100])], 200);

        $this->runJob('build a custom dashboard');

        $this->assertSame(0, RawSignal::where('source', 'freelance')->count());
    }

    public function test_skips_postings_older_than_max_age(): void
    {
        Http::fakeSequence()->push([
            $this->makePosting(['postedAt' => now()->subDays(30)->toIso8601String()]),
        ], 200);

        $this->runJob('build a custom dashboard');

        $this->assertSame(0, RawSignal::where('source', 'freelance')->count());
    }

    public function test_handles_budget_as_string_range(): void
    {
        Http::fakeSequence()->push([$this->makePosting(['budget' => '1000-5000'])], 200);

        $this->runJob('build a custom dashboard');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'freelance',
            'category' => 'build a custom dashboard',
        ]);
    }

    public function test_deduplicates_postings_with_same_url(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('build a custom dashboard');

        Http::fakeSequence()->push([$this->makePosting()], 200);
        $this->runJob('custom tool');

        $this->assertSame(1, RawSignal::where('source', 'freelance')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('build a custom dashboard');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'freelance',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makePosting()], 200);

        $this->runJob('build a custom dashboard');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'freelance',
            'query' => 'build a custom dashboard',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
