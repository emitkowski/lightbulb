<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestProductRoadmapsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestProductRoadmapsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scoring.serper.api_key', 'fake-serper-key');
        Config::set('scoring.serper.base_url', 'https://google.serper.dev');
        Config::set('ingestion.serper.roadmaps.results_per_tool', 5);
    }

    private function runJob(string $tool): void
    {
        (new IngestProductRoadmapsJob($tool))->handle(new IngestionService());
    }

    private function makeResult(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Notion Public Roadmap — Canny',
            'link' => 'https://notion.canny.io/feature-requests',
            'snippet' => 'Browse and vote on feature requests for Notion.',
        ], $overrides);
    }

    public function test_inserts_roadmap_pages_as_signals(): void
    {
        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);

        $this->runJob('Notion');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'serper_roadmaps',
            'category' => 'Notion',
        ]);
    }

    public function test_deduplicates_same_roadmap_url(): void
    {
        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);
        $this->runJob('Notion');

        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);
        $this->runJob('Notion');

        $this->assertSame(1, RawSignal::where('source', 'serper_roadmaps')->count());
    }

    public function test_logs_failed_run_when_serper_key_not_configured(): void
    {
        Config::set('scoring.serper.api_key', null);

        $this->runJob('Notion');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_roadmaps',
            'status' => 'failed',
        ]);
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('Notion');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_roadmaps',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push(['organic' => [$this->makeResult()]], 200);

        $this->runJob('Notion');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_roadmaps',
            'query' => 'Notion',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
