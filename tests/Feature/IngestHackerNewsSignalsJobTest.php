<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestHackerNewsSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestHackerNewsSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $query): void
    {
        (new IngestHackerNewsSignalsJob($query))->handle(new IngestionService());
    }

    private function makeHit(array $overrides = []): array
    {
        return array_merge([
            'objectID' => 'hn100',
            'title' => 'Ask HN: Is there a tool for X?',
            'story_text' => 'We have been doing this by hand.',
            'author' => 'hnuser',
            'points' => 75,
            'num_comments' => 30,
            '_tags' => ['ask_hn', 'story'],
            'url' => null,
            'created_at_i' => now()->subDays(5)->timestamp,
        ], $overrides);
    }

    public function test_inserts_qualifying_hits_as_signals(): void
    {
        Http::fakeSequence()->push(['hits' => [$this->makeHit()]], 200);

        $this->runJob('Ask HN: Is there a tool');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'hackernews',
            'source_id' => 'hn100',
            'category' => 'ask_hn',
        ]);
    }

    public function test_skips_show_hn_below_higher_threshold(): void
    {
        Http::fakeSequence()->push(['hits' => [$this->makeHit(['points' => 60, '_tags' => ['show_hn', 'story']])]], 200);

        $this->runJob('Show HN: I built this');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'hackernews', 'source_id' => 'hn100']);
    }

    public function test_deduplicates_hits(): void
    {
        Http::fakeSequence()->push(['hits' => [$this->makeHit()]], 200);
        $this->runJob('Ask HN: Is there a tool');

        Http::fakeSequence()->push(['hits' => [$this->makeHit()]], 200);
        $this->runJob('Ask HN: Is there a tool');

        $this->assertSame(1, RawSignal::where('source_id', 'hn100')->count());
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('Ask HN: Is there a tool');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'hackernews',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push(['hits' => [$this->makeHit()]], 200);

        $this->runJob('Ask HN: Is there a tool');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'hackernews',
            'query' => 'Ask HN: Is there a tool',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
