<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestDevToSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestDevToSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $tag): void
    {
        (new IngestDevToSignalsJob($tag))->handle(new IngestionService());
    }

    private function makeArticle(array $overrides = []): array
    {
        return array_merge([
            'id' => 55001,
            'title' => 'I built this because I couldn\'t find a tool that did it',
            'description' => 'After years of doing this manually, I decided to build my own solution.',
            'url' => 'https://dev.to/user/i-built-this-55001',
            'user' => ['username' => 'devuser'],
            'positive_reactions_count' => 20,
            'comments_count' => 8,
            'tag_list' => ['saas', 'startup'],
            'reading_time_minutes' => 5,
            'published_at' => now()->subDays(10)->toIso8601String(),
        ], $overrides);
    }

    public function test_inserts_qualifying_articles_with_gap_keywords_in_title(): void
    {
        Http::fakeSequence()->push([$this->makeArticle()], 200);

        $this->runJob('saas');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'devto',
            'source_id' => '55001',
            'category' => 'saas',
        ]);
    }

    public function test_inserts_articles_with_gap_keywords_in_description(): void
    {
        $article = $this->makeArticle([
            'title' => 'My indie hacker journey',
            'description' => 'We built this because nothing existed for this use case.',
        ]);
        Http::fakeSequence()->push([$article], 200);

        $this->runJob('saas');

        $this->assertDatabaseHas('raw_signals', ['source' => 'devto', 'source_id' => '55001']);
    }

    public function test_skips_articles_without_gap_keywords(): void
    {
        $article = $this->makeArticle([
            'title' => 'My favourite Laravel packages',
            'description' => 'A roundup of useful packages for everyday development.',
        ]);
        Http::fakeSequence()->push([$article], 200);

        $this->runJob('saas');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'devto', 'source_id' => '55001']);
    }

    public function test_skips_articles_below_min_reactions(): void
    {
        $article = $this->makeArticle(['positive_reactions_count' => 2]);
        Http::fakeSequence()->push([$article], 200);

        $this->runJob('saas');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'devto', 'source_id' => '55001']);
    }

    public function test_skips_articles_older_than_max_age(): void
    {
        $article = $this->makeArticle(['published_at' => now()->subDays(120)->toIso8601String()]);
        Http::fakeSequence()->push([$article], 200);

        $this->runJob('saas');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'devto', 'source_id' => '55001']);
    }

    public function test_deduplicates_articles_across_tags(): void
    {
        Http::fakeSequence()->push([$this->makeArticle()], 200);
        $this->runJob('saas');

        Http::fakeSequence()->push([$this->makeArticle()], 200);
        $this->runJob('startup');

        $this->assertSame(1, RawSignal::where('source_id', '55001')->count());
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('saas');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'devto',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push([$this->makeArticle()], 200);

        $this->runJob('saas');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'devto',
            'query' => 'saas',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
