<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestGitHubIssuesJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestGitHubIssuesJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $repo): void
    {
        (new IngestGitHubIssuesJob($repo))->handle(new IngestionService());
    }

    private function makeIssue(array $overrides = []): array
    {
        return array_merge([
            'number' => 1234,
            'title' => 'Add team collaboration features',
            'body' => 'We need team sync so multiple developers can share settings.',
            'html_url' => 'https://github.com/laravel/framework/issues/1234',
            'user' => ['login' => 'devuser'],
            'reactions' => ['+1' => 50, 'total_count' => 60],
            'comments' => 15,
            'labels' => [['name' => 'feature request']],
            'state' => 'open',
            'created_at' => now()->subDays(400)->toIso8601String(),
        ], $overrides);
    }

    public function test_inserts_qualifying_issues_as_signals(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeIssue()]], 200);

        $this->runJob('laravel/framework');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'github_issues',
            'source_id' => 'laravel/framework#1234',
            'category' => 'laravel/framework',
        ]);
    }

    public function test_skips_issues_younger_than_min_age(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeIssue(['created_at' => now()->subDays(30)->toIso8601String()])]], 200);

        $this->runJob('laravel/framework');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'github_issues', 'source_id' => 'laravel/framework#1234']);
    }

    public function test_skips_issues_below_min_thumbs_up(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeIssue(['reactions' => ['+1' => 5, 'total_count' => 5]])]], 200);

        $this->runJob('laravel/framework');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'github_issues', 'source_id' => 'laravel/framework#1234']);
    }

    public function test_deduplicates_issues(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeIssue()]], 200);
        $this->runJob('laravel/framework');

        Http::fakeSequence()->push(['items' => [$this->makeIssue()]], 200);
        $this->runJob('laravel/framework');

        $this->assertSame(1, RawSignal::where('source_id', 'laravel/framework#1234')->count());
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('laravel/framework');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'github_issues',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeIssue()]], 200);

        $this->runJob('laravel/framework');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'github_issues',
            'query' => 'laravel/framework',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }

    public function test_stores_thumbs_up_count_as_score(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeIssue(['reactions' => ['+1' => 75, 'total_count' => 90]])]], 200);

        $this->runJob('laravel/framework');

        $this->assertDatabaseHas('raw_signals', [
            'source_id' => 'laravel/framework#1234',
            'score' => 75,
        ]);
    }
}
