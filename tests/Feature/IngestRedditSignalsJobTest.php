<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestRedditSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestRedditSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $subreddit, string $query): void
    {
        (new IngestRedditSignalsJob($subreddit, $query))->handle(new IngestionService());
    }

    private function fakeReddit(array $posts = [], bool $tokenSuccess = true): void
    {
        if (! $tokenSuccess) {
            Http::fakeSequence()->push([], 401);
            return;
        }

        Http::fakeSequence()
            ->push(['access_token' => 'fake-token'], 200)
            ->push(['data' => ['children' => $posts]], 200);
    }

    private function makePost(array $overrides = []): array
    {
        return ['data' => array_merge([
            'id' => 'post1',
            'title' => 'I wish there was a tool for this',
            'selftext' => 'We have been doing this manually for years.',
            'author' => 'testuser',
            'score' => 50,
            'num_comments' => 10,
            'subreddit' => 'SaaS',
            'permalink' => '/r/SaaS/comments/post1/test/',
            'url' => 'https://reddit.com/r/SaaS/comments/post1/test/',
            'is_self' => true,
            'link_flair_text' => null,
            'created_utc' => now()->subDays(2)->timestamp,
        ], $overrides)];
    }

    public function test_inserts_qualifying_posts_as_signals(): void
    {
        $this->fakeReddit([$this->makePost()]);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'reddit',
            'source_id' => 'post1',
            'category' => 'SaaS',
        ]);
    }

    public function test_skips_posts_below_min_score(): void
    {
        $this->fakeReddit([$this->makePost(['score' => 5])]);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'reddit', 'source_id' => 'post1']);
    }

    public function test_deduplicates_posts(): void
    {
        $this->fakeReddit([$this->makePost()]);
        $this->runJob('SaaS', 'I wish there was');

        $this->fakeReddit([$this->makePost()]);
        $this->runJob('SaaS', 'I wish there was');

        $this->assertSame(1, RawSignal::where('source_id', 'post1')->count());
    }

    public function test_logs_failed_run_when_token_fetch_fails(): void
    {
        $this->fakeReddit(tokenSuccess: false);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'reddit',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        $this->fakeReddit([$this->makePost()]);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'reddit',
            'query' => 'I wish there was',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }

    public function test_logs_partial_run_when_rate_limited(): void
    {
        Http::fakeSequence()
            ->push(['access_token' => 'fake-token'], 200)
            ->push([], 429);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'reddit',
            'status' => 'partial',
        ]);
    }

    public function test_skips_posts_older_than_max_age(): void
    {
        $oldPost = $this->makePost([
            'id' => 'oldpost',
            'created_utc' => now()->subDays(30)->timestamp,
        ]);

        $this->fakeReddit([$oldPost]);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseMissing('raw_signals', ['source_id' => 'oldpost']);
        $this->assertDatabaseHas('ingestion_runs', ['status' => 'success', 'signals_inserted' => 0]);
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()
            ->push(['access_token' => 'fake-token'], 200)
            ->push([], 503);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'reddit',
            'status' => 'failed',
        ]);
    }

    public function test_logs_failed_run_when_token_request_throws(): void
    {
        Http::fake(['*' => function () {
            throw new \RuntimeException('Connection refused');
        }]);

        $this->runJob('SaaS', 'I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'reddit',
            'status' => 'failed',
            'error_message' => 'Failed to obtain Reddit access token',
        ]);
    }
}
