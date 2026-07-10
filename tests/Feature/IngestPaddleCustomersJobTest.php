<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestPaddleCustomersJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestPaddleCustomersJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.paddle_customers.url', 'https://www.paddle.com/customers');
    }

    private function runJob(): void
    {
        (new IngestPaddleCustomersJob())->handle(new IngestionService());
    }

    /**
     * Mirrors the real page's shape (confirmed live 2026-07-10): each case study
     * appears twice — a compact split link and a fuller card with a longer sentence.
     */
    private function makePage(): string
    {
        return <<<'HTML'
            <html><body>
            <nav>
                <a href="/customers/helping-nexus-mods-unlock-the-gaming-community"><p>Nexus Mods</p><p>9x revenue uplift in China</p></a>
            </nav>
            <div class="grid">
                <a href="/customers/helping-nexus-mods-unlock-the-gaming-community"><span>Nexus Mods migrated 100k+ subscribers and grew China revenue 9x</span></a>
                <a href="/customers/bouncer"><span>Bouncer scaled revenue 5x while keeping finance operations lean with Paddle</span></a>
                <a href="/customers">All customers</a>
                <a href="/customers/all">View all</a>
            </div>
            </body></html>
            HTML;
    }

    public function test_inserts_case_studies_as_signals(): void
    {
        Http::fake(['*' => Http::response($this->makePage(), 200)]);

        $this->runJob();

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'paddle_customers',
            'source_id' => 'bouncer',
            'source_url' => 'https://www.paddle.com/customers/bouncer',
        ]);
    }

    public function test_keeps_the_longer_text_when_a_case_study_appears_twice(): void
    {
        Http::fake(['*' => Http::response($this->makePage(), 200)]);

        $this->runJob();

        $signal = RawSignal::where('source_id', 'helping-nexus-mods-unlock-the-gaming-community')->first();

        $this->assertSame('Nexus Mods migrated 100k+ subscribers and grew China revenue 9x', $signal->title);
    }

    public function test_skips_the_index_links(): void
    {
        Http::fake(['*' => Http::response($this->makePage(), 200)]);

        $this->runJob();

        $this->assertSame(2, RawSignal::where('source', 'paddle_customers')->count());
    }

    public function test_logs_failed_run_on_http_error(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->runJob();

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'paddle_customers',
            'status' => 'failed',
        ]);
    }

    public function test_logs_failed_run_on_unexpected_exception(): void
    {
        Http::fake(fn () => throw new \RuntimeException('connection reset'));

        $this->runJob();

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'paddle_customers',
            'status' => 'failed',
            'error_message' => 'connection reset',
        ]);
    }

    public function test_handles_malformed_html_without_throwing(): void
    {
        Http::fake(['*' => Http::response('<html><body><a href="/customers/broken-tag"><p>Unclosed tag', 200)]);

        $this->runJob();

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'paddle_customers',
            'source_id' => 'broken-tag',
        ]);
    }

    public function test_deduplicates_across_runs(): void
    {
        Http::fake(['*' => Http::response($this->makePage(), 200)]);

        $this->runJob();
        $this->runJob();

        $this->assertSame(2, RawSignal::where('source', 'paddle_customers')->count());
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fake(['*' => Http::response($this->makePage(), 200)]);

        $this->runJob();

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'paddle_customers',
            'signals_found' => 2,
            'signals_inserted' => 2,
            'status' => 'success',
        ]);
    }
}
