<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestVSCodeMarketplaceSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestVSCodeMarketplaceSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $query): void
    {
        (new IngestVSCodeMarketplaceSignalsJob($query))->handle(new IngestionService());
    }

    private function makeExtensionResponse(array $extensions): array
    {
        return [
            'results' => [[
                'extensions' => $extensions,
            ]],
        ];
    }

    private function makeExtension(array $overrides = []): array
    {
        return array_merge([
            'extensionName' => 'time-tracker',
            'displayName' => 'Time Tracker Pro',
            'shortDescription' => 'Track time spent on files and projects.',
            'publisher' => [
                'publisherName' => 'somepublisher',
                'displayName' => 'Some Publisher',
            ],
            'categories' => ['Productivity'],
            'tags' => ['time', 'tracking'],
            'statistics' => [
                ['statisticName' => 'install', 'value' => 80000],
                ['statisticName' => 'weightedRating', 'value' => 3.2],
                ['statisticName' => 'ratingcount', 'value' => 150],
            ],
        ], $overrides);
    }

    public function test_inserts_qualifying_extensions_as_signals(): void
    {
        Http::fakeSequence()->push($this->makeExtensionResponse([$this->makeExtension()]), 200);

        $this->runJob('time tracking');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'vscode_marketplace',
            'source_id' => 'somepublisher.time-tracker',
            'category' => 'time tracking',
        ]);
    }

    public function test_skips_extensions_below_min_install_count(): void
    {
        $ext = $this->makeExtension([
            'statistics' => [
                ['statisticName' => 'install', 'value' => 10000],
                ['statisticName' => 'weightedRating', 'value' => 3.2],
                ['statisticName' => 'ratingcount', 'value' => 150],
            ],
        ]);

        Http::fakeSequence()->push($this->makeExtensionResponse([$ext]), 200);

        $this->runJob('time tracking');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'vscode_marketplace', 'source_id' => 'somepublisher.time-tracker']);
    }

    public function test_skips_extensions_with_high_rating(): void
    {
        $ext = $this->makeExtension([
            'statistics' => [
                ['statisticName' => 'install', 'value' => 80000],
                ['statisticName' => 'weightedRating', 'value' => 4.5],
                ['statisticName' => 'ratingcount', 'value' => 150],
            ],
        ]);

        Http::fakeSequence()->push($this->makeExtensionResponse([$ext]), 200);

        $this->runJob('time tracking');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'vscode_marketplace', 'source_id' => 'somepublisher.time-tracker']);
    }

    public function test_skips_extensions_with_too_few_ratings(): void
    {
        $ext = $this->makeExtension([
            'statistics' => [
                ['statisticName' => 'install', 'value' => 80000],
                ['statisticName' => 'weightedRating', 'value' => 3.2],
                ['statisticName' => 'ratingcount', 'value' => 10],
            ],
        ]);

        Http::fakeSequence()->push($this->makeExtensionResponse([$ext]), 200);

        $this->runJob('time tracking');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'vscode_marketplace', 'source_id' => 'somepublisher.time-tracker']);
    }

    public function test_deduplicates_extensions_across_queries(): void
    {
        Http::fakeSequence()->push($this->makeExtensionResponse([$this->makeExtension()]), 200);
        $this->runJob('time tracking');

        Http::fakeSequence()->push($this->makeExtensionResponse([$this->makeExtension()]), 200);
        $this->runJob('productivity');

        $this->assertSame(1, RawSignal::where('source_id', 'somepublisher.time-tracker')->count());
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('time tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'vscode_marketplace',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push($this->makeExtensionResponse([$this->makeExtension()]), 200);

        $this->runJob('time tracking');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'vscode_marketplace',
            'query' => 'time tracking',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }

    public function test_stores_install_count_as_score(): void
    {
        Http::fakeSequence()->push($this->makeExtensionResponse([$this->makeExtension()]), 200);

        $this->runJob('time tracking');

        $this->assertDatabaseHas('raw_signals', [
            'source_id' => 'somepublisher.time-tracker',
            'score' => 80000,
        ]);
    }
}
