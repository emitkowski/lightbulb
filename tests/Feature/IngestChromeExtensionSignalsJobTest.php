<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestChromeExtensionSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestChromeExtensionSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.chrome.actor_id', 'vujeen/chrome-web-store-scraper');
        Config::set('ingestion.apify.chrome.min_install_count', 10000);
        Config::set('ingestion.apify.chrome.max_star_rating', 4.0);
        Config::set('ingestion.apify.chrome.max_items_per_category', 50);
    }

    private function runJob(string $category): void
    {
        (new IngestChromeExtensionSignalsJob($category))->handle(new IngestionService(), new ApifyService());
    }

    private function makeExtension(array $overrides = []): array
    {
        return array_merge([
            'name' => 'TimeTracker for Chrome',
            'description' => 'Track time directly in your browser across all websites.',
            'url' => 'https://chrome.google.com/webstore/detail/timetracker/abc123def456',
            'installCount' => 75000,
            'rating' => 3.8,
            'ratingCount' => 1200,
            'developer' => 'DevStudio Inc.',
            'version' => '2.1.0',
        ], $overrides);
    }

    public function test_inserts_extensions_with_high_installs_and_mediocre_rating(): void
    {
        Http::fakeSequence()->push([$this->makeExtension()], 200);

        $this->runJob('productivity');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'chrome_webstore',
            'category' => 'productivity',
        ]);
    }

    public function test_skips_extensions_below_min_install_count(): void
    {
        Http::fakeSequence()->push([$this->makeExtension(['installCount' => 5000])], 200);

        $this->runJob('productivity');

        $this->assertSame(0, RawSignal::where('source', 'chrome_webstore')->count());
    }

    public function test_skips_extensions_above_max_star_rating(): void
    {
        Http::fakeSequence()->push([$this->makeExtension(['rating' => 4.8])], 200);

        $this->runJob('productivity');

        $this->assertSame(0, RawSignal::where('source', 'chrome_webstore')->count());
    }

    public function test_deduplicates_extensions_by_url(): void
    {
        Http::fakeSequence()->push([$this->makeExtension()], 200);
        $this->runJob('productivity');

        Http::fakeSequence()->push([$this->makeExtension()], 200);
        $this->runJob('developer-tools');

        $this->assertSame(1, RawSignal::where('source', 'chrome_webstore')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('productivity');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'chrome_webstore',
            'status' => 'failed',
        ]);
    }

    public function test_handles_install_count_as_string_with_commas(): void
    {
        Http::fakeSequence()->push([$this->makeExtension(['installCount' => '75,000'])], 200);

        $this->runJob('productivity');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'chrome_webstore',
            'category' => 'productivity',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makeExtension()], 200);

        $this->runJob('productivity');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'chrome_webstore',
            'query' => 'productivity',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
