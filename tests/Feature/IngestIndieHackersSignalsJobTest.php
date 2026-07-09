<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestIndieHackersSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestIndieHackersSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scoring.serper.api_key', 'fake-serper-key');
        Config::set('scoring.serper.base_url', 'https://google.serper.dev');
        Config::set('ingestion.serper.indiehackers.results_per_query', 5);
    }

    private function runJob(string $query): void
    {
        (new IngestIndieHackersSignalsJob($query))->handle(new IngestionService());
    }

    private function makeSerperResponse(array $organic = []): array
    {
        return ['organic' => $organic];
    }

    private function makeResult(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Ask IH: does anyone know a tool for client reporting?',
            'link' => 'https://www.indiehackers.com/post/does-anyone-know-a-tool',
            'snippet' => 'I keep manually compiling weekly client reports, is there a tool for this?',
        ], $overrides);
    }

    public function test_inserts_organic_results_as_signals(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('does anyone know a tool that');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'indiehackers',
            'category' => 'does anyone know a tool that',
        ]);
    }

    public function test_restricts_search_to_indiehackers_site(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('does anyone know a tool that');

        Http::assertSent(function ($request) {
            return ($request['q'] ?? null) === 'site:indiehackers.com "does anyone know a tool that"';
        });
    }

    public function test_deduplicates_the_same_url_across_different_queries(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);
        $this->runJob('does anyone know a tool that');

        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);
        $this->runJob('looking for something that');

        // Same URL found via two different query phrases should only be stored once
        $this->assertSame(1, RawSignal::where('source', 'indiehackers')->count());
    }

    public function test_skips_results_without_a_url(): void
    {
        $result = $this->makeResult(['link' => null]);
        unset($result['link']);
        Http::fakeSequence()->push($this->makeSerperResponse([$result]), 200);

        $this->runJob('does anyone know a tool that');

        $this->assertSame(0, RawSignal::where('source', 'indiehackers')->count());
    }

    public function test_logs_failed_run_when_serper_key_not_configured(): void
    {
        Config::set('scoring.serper.api_key', null);

        $this->runJob('does anyone know a tool that');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'indiehackers',
            'status' => 'failed',
        ]);
    }

    public function test_inserts_zero_signals_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('does anyone know a tool that');

        $this->assertSame(0, RawSignal::where('source', 'indiehackers')->count());
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('does anyone know a tool that');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'indiehackers',
            'query' => 'does anyone know a tool that',
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

        $this->runJob('does anyone know a tool that');

        $first = RawSignal::where('source_url', 'https://example.com/first')->first();
        $second = RawSignal::where('source_url', 'https://example.com/second')->first();

        $this->assertGreaterThan($second->score, $first->score);
    }
}
