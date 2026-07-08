<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestTwitterSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestTwitterSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.twitter.bearer_token', 'fake-bearer-token');
    }

    private function runJob(string $query): void
    {
        (new IngestTwitterSignalsJob($query))->handle(new IngestionService());
    }

    private function makeTweet(array $overrides = []): array
    {
        return array_merge([
            'id' => 'tweet1',
            'text' => 'I wish there was a tool that automated this for freelancers.',
            'author_id' => 'user1',
            'created_at' => now()->subHours(2)->toIso8601String(),
            'public_metrics' => [
                'like_count' => 20,
                'reply_count' => 4,
                'retweet_count' => 2,
                'quote_count' => 0,
            ],
        ], $overrides);
    }

    public function test_returns_failed_run_when_no_bearer_token_configured(): void
    {
        Config::set('ingestion.twitter.bearer_token', null);

        $this->runJob('I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'twitter',
            'status' => 'failed',
        ]);
        Http::assertNothingSent();
    }

    public function test_inserts_qualifying_tweets_as_signals(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [$this->makeTweet()]], 200),
        ]);

        $this->runJob('I wish there was');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'twitter',
            'source_id' => 'tweet1',
            'score' => 20,
        ]);
    }

    public function test_resolves_username_from_includes(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [$this->makeTweet()],
                'includes' => ['users' => [['id' => 'user1', 'username' => 'foundertest']]],
            ], 200),
        ]);

        $this->runJob('I wish there was');

        $this->assertDatabaseHas('raw_signals', [
            'source_id' => 'tweet1',
            'author' => 'foundertest',
        ]);
    }

    public function test_skips_tweets_below_min_likes(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [$this->makeTweet(['public_metrics' => ['like_count' => 1, 'reply_count' => 0, 'retweet_count' => 0, 'quote_count' => 0]])]], 200),
        ]);

        $this->runJob('I wish there was');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'twitter', 'source_id' => 'tweet1']);
    }

    public function test_deduplicates_tweets(): void
    {
        Http::fake(['*' => Http::response(['data' => [$this->makeTweet()]], 200)]);
        $this->runJob('I wish there was');
        $this->runJob('I wish there was');

        $this->assertSame(1, RawSignal::where('source_id', 'tweet1')->count());
    }

    public function test_logs_partial_run_when_rate_limited(): void
    {
        Http::fake(['*' => Http::response([], 429)]);

        $this->runJob('I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'twitter',
            'status' => 'partial',
        ]);
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fake(['*' => Http::response([], 503)]);

        $this->runJob('I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'twitter',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fake(['*' => Http::response(['data' => [$this->makeTweet()]], 200)]);

        $this->runJob('I wish there was');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'twitter',
            'query' => 'I wish there was',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
