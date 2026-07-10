<?php

namespace Tests\Feature;

use App\Models\IngestionRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestionRunCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Broad fake so every source's HTTP call resolves without touching the network.
        Http::fake(fn () => Http::response([], 200));
    }

    public function test_free_only_rejects_a_keyed_source(): void
    {
        $this->artisan('ingestion:run', ['--source' => 'reddit', '--free-only' => true])
            ->expectsOutputToContain("'reddit' requires an API key")
            ->assertExitCode(1);

        $this->assertSame(0, IngestionRun::count());
    }

    public function test_free_only_runs_only_zero_key_sources(): void
    {
        $this->artisan('ingestion:run', ['--free-only' => true, '--limit' => 1])
            ->assertExitCode(0);

        $sourcesRun = IngestionRun::distinct()->pluck('source')->all();

        $this->assertNotEmpty($sourcesRun);
        foreach ($sourcesRun as $source) {
            $this->assertContains($source, [
                'hackernews', 'github_issues', 'vscode_marketplace',
                'stackoverflow', 'devto', 'larajobs', 'paddle_customers',
            ]);
        }
        $this->assertNotContains('reddit', $sourcesRun);
        $this->assertNotContains('g2', $sourcesRun);
    }

    public function test_limit_caps_the_number_of_dispatched_queries(): void
    {
        $this->artisan('ingestion:run', ['--source' => 'hackernews', '--limit' => 2])
            ->assertExitCode(0);

        $this->assertSame(2, IngestionRun::where('source', 'hackernews')->count());
    }

    public function test_limit_applies_to_both_dimensions_of_reddit(): void
    {
        $this->artisan('ingestion:run', ['--source' => 'reddit', '--limit' => 2])
            ->assertExitCode(0);

        // 2 subreddits x 2 queries = 4 runs
        $this->assertSame(4, IngestionRun::where('source', 'reddit')->count());
    }

    public function test_unknown_source_still_fails(): void
    {
        $this->artisan('ingestion:run', ['--source' => 'not-a-real-source'])
            ->assertExitCode(1);
    }

    public function test_apify_gaps_fans_out_to_github_reddit_and_actor_health_runs(): void
    {
        $this->artisan('ingestion:run', ['--source' => 'apify_gaps', '--limit' => 1])
            ->assertExitCode(0);

        // 1 GitHub repo + 1 Reddit query + 1 monitored actor = 3 ingestion_runs,
        // split across the reused 'github_issues'/'reddit' sources plus the new
        // 'apify_actor_gaps' source.
        $sourcesRun = IngestionRun::pluck('source')->all();
        $this->assertCount(3, $sourcesRun);
        $this->assertContains('github_issues', $sourcesRun);
        $this->assertContains('reddit', $sourcesRun);
        $this->assertContains('apify_actor_gaps', $sourcesRun);
    }

    public function test_paddle_customers_dispatches_a_single_job(): void
    {
        $this->artisan('ingestion:run', ['--source' => 'paddle_customers'])
            ->assertExitCode(0);

        $this->assertSame(1, IngestionRun::where('source', 'paddle_customers')->count());
    }

    public function test_stripe_customers_limit_caps_the_number_of_categories(): void
    {
        $this->artisan('ingestion:run', ['--source' => 'stripe_customers', '--limit' => 2])
            ->assertExitCode(0);

        $this->assertSame(2, IngestionRun::where('source', 'stripe_customers')->count());
    }
}
