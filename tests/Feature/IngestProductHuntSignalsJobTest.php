<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestProductHuntSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestProductHuntSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.producthunt.api_key', 'fake-ph-token');
    }

    private function runJob(string $topic): void
    {
        (new IngestProductHuntSignalsJob($topic))->handle(new IngestionService());
    }

    private function makeResponse(array $comments = [], array $postOverrides = []): array
    {
        $post = array_merge([
            'id' => 'post-abc',
            'name' => 'AwesomeTool',
            'tagline' => 'The best tool for developers',
            'url' => 'https://www.producthunt.com/posts/awesometool',
            'votesCount' => 300,
            'createdAt' => now()->subDays(5)->toIso8601String(),
        ], $postOverrides);

        $post['comments'] = ['edges' => $comments];

        return [
            'data' => [
                'posts' => [
                    'edges' => [['node' => $post]],
                ],
            ],
        ];
    }

    private function makeComment(array $overrides = []): array
    {
        return ['node' => array_merge([
            'id' => 'comment-xyz',
            'body' => 'Love this but it is missing team features I need',
            'votesCount' => 5,
            'createdAt' => now()->subDays(3)->toIso8601String(),
        ], $overrides)];
    }

    public function test_inserts_qualifying_gap_comments_as_signals(): void
    {
        Http::fakeSequence()->push($this->makeResponse([$this->makeComment()]), 200);

        $this->runJob('developer-tools');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'producthunt',
            'source_id' => 'comment-xyz',
            'category' => 'developer-tools',
        ]);
    }

    public function test_skips_comments_without_gap_keywords(): void
    {
        $comment = $this->makeComment(['body' => 'This is amazing, love it!']);
        Http::fakeSequence()->push($this->makeResponse([$comment]), 200);

        $this->runJob('developer-tools');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'producthunt', 'source_id' => 'comment-xyz']);
    }

    public function test_skips_comments_below_min_votes(): void
    {
        $comment = $this->makeComment(['votesCount' => 1]);
        Http::fakeSequence()->push($this->makeResponse([$comment]), 200);

        $this->runJob('developer-tools');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'producthunt', 'source_id' => 'comment-xyz']);
    }

    public function test_logs_failed_run_when_api_key_not_configured(): void
    {
        Config::set('ingestion.producthunt.api_key', null);

        $this->runJob('developer-tools');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'producthunt',
            'status' => 'failed',
        ]);
    }

    public function test_deduplicates_comments(): void
    {
        Http::fakeSequence()->push($this->makeResponse([$this->makeComment()]), 200);
        $this->runJob('developer-tools');

        Http::fakeSequence()->push($this->makeResponse([$this->makeComment()]), 200);
        $this->runJob('saas');

        $this->assertSame(1, RawSignal::where('source_id', 'comment-xyz')->count());
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('developer-tools');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'producthunt',
            'status' => 'failed',
        ]);
    }

    public function test_logs_failed_run_on_graphql_error(): void
    {
        Http::fakeSequence()->push(['errors' => [['message' => 'Unauthorized']]], 200);

        $this->runJob('developer-tools');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'producthunt',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push($this->makeResponse([$this->makeComment()]), 200);

        $this->runJob('developer-tools');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'producthunt',
            'query' => 'developer-tools',
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
