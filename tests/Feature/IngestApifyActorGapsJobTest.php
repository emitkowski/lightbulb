<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestApifyActorGapsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestApifyActorGapsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify_gaps.min_runs_30d', 20);
        Config::set('ingestion.apify_gaps.max_failure_rate', 0.15);
        Config::set('ingestion.apify_gaps.max_review_rating', 2.0);
        Config::set('ingestion.apify_gaps.min_review_count_for_rating_signal', 3);
    }

    private function runJob(string $actorId): void
    {
        (new IngestApifyActorGapsJob($actorId))->handle(new IngestionService(), new ApifyService());
    }

    private function fakeActorInfo(array $overrides = []): void
    {
        $data = array_merge([
            'title' => 'Some Scraper',
            'categories' => ['JOBS'],
            'stats' => [
                'actorReviewCount' => 0,
                'actorReviewRating' => 0,
                'publicActorRunStats30Days' => [
                    'ABORTED' => 0, 'FAILED' => 0, 'SUCCEEDED' => 100, 'TIMED-OUT' => 0, 'TOTAL' => 100,
                ],
            ],
        ], $overrides);

        Http::fake(['*' => Http::response(['data' => $data], 200)]);
    }

    public function test_inserts_a_gap_signal_when_failure_rate_exceeds_threshold(): void
    {
        $this->fakeActorInfo([
            'stats' => [
                'actorReviewCount' => 0,
                'actorReviewRating' => 0,
                'publicActorRunStats30Days' => [
                    'ABORTED' => 5, 'FAILED' => 10, 'SUCCEEDED' => 60, 'TIMED-OUT' => 5, 'TOTAL' => 80,
                ],
            ],
        ]);

        $this->runJob('broken/actor');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'apify_actor_gaps',
            'category' => 'JOBS',
        ]);
    }

    public function test_inserts_a_gap_signal_when_review_rating_is_low_with_enough_reviews(): void
    {
        $this->fakeActorInfo([
            'stats' => [
                'actorReviewCount' => 5,
                'actorReviewRating' => 1.5,
                'publicActorRunStats30Days' => [
                    'ABORTED' => 0, 'FAILED' => 0, 'SUCCEEDED' => 100, 'TIMED-OUT' => 0, 'TOTAL' => 100,
                ],
            ],
        ]);

        $this->runJob('badly-reviewed/actor');

        $this->assertSame(1, RawSignal::where('source', 'apify_actor_gaps')->count());
    }

    public function test_does_not_flag_a_healthy_actor(): void
    {
        $this->fakeActorInfo();

        $this->runJob('healthy/actor');

        $this->assertSame(0, RawSignal::where('source', 'apify_actor_gaps')->count());
    }

    public function test_ignores_failure_rate_below_the_minimum_run_volume(): void
    {
        // 5 out of 10 runs failed (50%) but total is below min_runs_30d — too small a
        // sample to trust, matches the project's own lesson that platform stats are noisy.
        $this->fakeActorInfo([
            'stats' => [
                'actorReviewCount' => 0,
                'actorReviewRating' => 0,
                'publicActorRunStats30Days' => [
                    'ABORTED' => 0, 'FAILED' => 5, 'SUCCEEDED' => 5, 'TIMED-OUT' => 0, 'TOTAL' => 10,
                ],
            ],
        ]);

        $this->runJob('low-volume/actor');

        $this->assertSame(0, RawSignal::where('source', 'apify_actor_gaps')->count());
    }

    public function test_ignores_low_rating_with_too_few_reviews(): void
    {
        $this->fakeActorInfo([
            'stats' => [
                'actorReviewCount' => 1,
                'actorReviewRating' => 1.0,
                'publicActorRunStats30Days' => [
                    'ABORTED' => 0, 'FAILED' => 0, 'SUCCEEDED' => 100, 'TIMED-OUT' => 0, 'TOTAL' => 100,
                ],
            ],
        ]);

        $this->runJob('one-bad-review/actor');

        $this->assertSame(0, RawSignal::where('source', 'apify_actor_gaps')->count());
    }

    public function test_logs_failed_run_when_apify_token_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);
        Http::fake();

        $this->runJob('someone/actor');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'apify_actor_gaps',
            'status' => 'failed',
        ]);
        Http::assertNothingSent();
    }

    public function test_logs_failed_run_on_http_error(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->runJob('someone/actor');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'apify_actor_gaps',
            'status' => 'failed',
        ]);
    }

    public function test_deduplicates_within_the_same_week(): void
    {
        $this->fakeActorInfo([
            'stats' => [
                'actorReviewCount' => 0,
                'actorReviewRating' => 0,
                'publicActorRunStats30Days' => [
                    'ABORTED' => 5, 'FAILED' => 10, 'SUCCEEDED' => 60, 'TIMED-OUT' => 5, 'TOTAL' => 80,
                ],
            ],
        ]);

        $this->runJob('broken/actor');
        $this->runJob('broken/actor');

        $this->assertSame(1, RawSignal::where('source', 'apify_actor_gaps')->count());
    }
}
