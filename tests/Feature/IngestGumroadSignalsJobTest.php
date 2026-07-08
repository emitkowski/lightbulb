<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestGumroadSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestGumroadSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
        Config::set('ingestion.apify.gumroad.actor_id', 'muhammetakkurtt/gumroad-scraper');
        Config::set('ingestion.apify.gumroad.min_rating_count', 20);
    }

    private function runJob(string $searchTerm): void
    {
        (new IngestGumroadSignalsJob($searchTerm))->handle(new IngestionService(), new ApifyService());
    }

    /**
     * Shape matches the real muhammetakkurtt/gumroad-scraper output, confirmed via a live call.
     */
    private function makeProduct(array $overrides = []): array
    {
        return array_merge([
            'id' => 'gumroad-abc123',
            'name' => 'Client Reporting Template Pack',
            'description' => 'Save hours every month with these plug-and-play reporting templates.',
            'url' => 'https://gumroad.com/l/client-report-templates',
            'seller' => ['name' => 'DesignStudio'],
            'ratings' => ['count' => 150, 'average' => 4.7],
            'price_cents' => 2900,
            'currency_code' => 'usd',
        ], $overrides);
    }

    public function test_inserts_high_rating_count_products_as_signals(): void
    {
        Http::fakeSequence()->push([$this->makeProduct()], 200);

        $this->runJob('client reporting template');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'gumroad',
            'category' => 'client reporting template',
        ]);
    }

    public function test_skips_products_below_min_rating_count(): void
    {
        Http::fakeSequence()->push([$this->makeProduct(['ratings' => ['count' => 5, 'average' => 4.7]])], 200);

        $this->runJob('client reporting template');

        $this->assertSame(0, RawSignal::where('source', 'gumroad')->count());
    }

    public function test_deduplicates_products_with_same_id(): void
    {
        Http::fakeSequence()->push([$this->makeProduct()], 200);
        $this->runJob('client reporting template');

        Http::fakeSequence()->push([$this->makeProduct()], 200);
        $this->runJob('client report');

        $this->assertSame(1, RawSignal::where('source', 'gumroad')->count());
    }

    public function test_skips_products_missing_url_or_title(): void
    {
        Http::fakeSequence()->push([$this->makeProduct(['url' => null])], 200);

        $this->runJob('client reporting template');

        $this->assertSame(0, RawSignal::where('source', 'gumroad')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->runJob('client reporting template');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'gumroad',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makeProduct()], 200);

        $this->runJob('client reporting template');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'gumroad',
            'query' => 'client reporting template',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
