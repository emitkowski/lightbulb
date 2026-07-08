<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestAlternativesSearchJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestAlternativesSearchJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scoring.serper.api_key', 'fake-serper-key');
        Config::set('scoring.serper.base_url', 'https://google.serper.dev');
        Config::set('ingestion.serper.alternatives.query_templates', [
            'alternatives to {tool}',
        ]);
        Config::set('ingestion.serper.alternatives.results_per_query', 5);
    }

    private function runJob(string $tool): void
    {
        (new IngestAlternativesSearchJob($tool))->handle(new IngestionService());
    }

    private function makeSerperResponse(array $organic = []): array
    {
        return ['organic' => $organic];
    }

    private function makeResult(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Top 10 Bonsai alternatives for freelancers',
            'link' => 'https://example.com/bonsai-alternatives',
            'snippet' => 'Looking for something that does what Bonsai does but better?',
        ], $overrides);
    }

    public function test_inserts_organic_results_as_signals(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('Bonsai');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'serper_alternatives',
            'category' => 'Bonsai',
        ]);
    }

    public function test_source_id_is_deterministic_and_unique_per_tool_and_url(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);
        $this->runJob('Bonsai');

        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);
        $this->runJob('Bonsai');

        // Same tool + same URL should only be stored once
        $this->assertSame(1, RawSignal::where('source', 'serper_alternatives')->count());
    }

    public function test_skips_results_without_a_url(): void
    {
        $result = $this->makeResult(['link' => null]);
        unset($result['link']);
        Http::fakeSequence()->push($this->makeSerperResponse([$result]), 200);

        $this->runJob('Bonsai');

        $this->assertSame(0, RawSignal::where('source', 'serper_alternatives')->count());
    }

    public function test_logs_failed_run_when_serper_key_not_configured(): void
    {
        Config::set('scoring.serper.api_key', null);

        $this->runJob('Bonsai');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_alternatives',
            'status' => 'failed',
        ]);
    }

    public function test_inserts_zero_signals_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('Bonsai');

        $this->assertSame(0, RawSignal::where('source', 'serper_alternatives')->count());
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('Bonsai');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'serper_alternatives',
            'query' => 'Bonsai',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }

    public function test_position_determines_score(): void
    {
        $results = [
            $this->makeResult(['link' => 'https://example.com/first', 'title' => 'First result']),
            $this->makeResult(['link' => 'https://example.com/second', 'title' => 'Second result']),
        ];
        Http::fakeSequence()->push($this->makeSerperResponse($results), 200);

        $this->runJob('Bonsai');

        $first = RawSignal::where('source_url', 'https://example.com/first')->first();
        $second = RawSignal::where('source_url', 'https://example.com/second')->first();

        $this->assertGreaterThan($second->score, $first->score);
    }
}
