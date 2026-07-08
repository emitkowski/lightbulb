<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestCapterraBuyerGuidesJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestCapterraBuyerGuidesJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scoring.serper.api_key', 'fake-serper-key');
        Config::set('scoring.serper.base_url', 'https://google.serper.dev');
        Config::set('ingestion.serper.capterra.results_per_category', 5);
    }

    private function runJob(string $category): void
    {
        (new IngestCapterraBuyerGuidesJob($category))->handle(new IngestionService());
    }

    private function makeResult(array $overrides = []): array
    {
        return array_merge([
            'title' => "Best Time Tracking Software — Capterra Buyer's Guide 2026",
            'link' => 'https://www.capterra.com/time-tracking-software/buyers-guide',
            'snippet' => 'Compare the best time tracking tools. Read buyer reviews and find the right fit.',
        ], $overrides);
    }

    public function test_inserts_buyer_guide_pages_as_signals(): void
    {
        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'serper_capterra',
            'category' => 'time-tracking',
        ]);
    }

    public function test_deduplicates_same_guide_url(): void
    {
        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);
        $this->runJob('time-tracking');

        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);
        $this->runJob('time-tracking');

        $this->assertSame(1, RawSignal::where('source', 'serper_capterra')->count());
    }

    public function test_logs_failed_run_when_serper_key_not_configured(): void
    {
        Config::set('scoring.serper.api_key', null);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_capterra',
            'status' => 'failed',
        ]);
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_capterra',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);

        $this->runJob('time-tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_capterra',
            'query' => 'time-tracking',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
