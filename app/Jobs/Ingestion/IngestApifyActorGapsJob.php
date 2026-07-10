<?php

namespace App\Jobs\Ingestion;

use Throwable;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IngestApifyActorGapsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $actorId) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);
        $run = $ingestionService->startRun('apify_actor_gaps', $this->actorId);

        if (! $apifyService->hasToken()) {
            $ingestionService->finishRun($run, [
                'found' => 0, 'inserted' => 0, 'skipped' => 0,
                'status' => 'failed', 'error' => 'Apify token not configured',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
            return;
        }

        try {
            $info = $apifyService->getActorInfo($this->actorId);
            $stats = ['found' => 1, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

            $runStats = $info['stats']['publicActorRunStats30Days'] ?? [];
            $totalRuns = (int) ($runStats['TOTAL'] ?? 0);
            $failed = (int) ($runStats['FAILED'] ?? 0);
            $aborted = (int) ($runStats['ABORTED'] ?? 0);
            $timedOut = (int) ($runStats['TIMED-OUT'] ?? 0);
            $failureRate = $totalRuns > 0 ? ($failed + $aborted + $timedOut) / $totalRuns : 0.0;

            $reviewCount = (int) ($info['stats']['actorReviewCount'] ?? 0);
            $reviewRating = (float) ($info['stats']['actorReviewRating'] ?? 0.0);

            $minRuns = (int) config('ingestion.apify_gaps.min_runs_30d', 20);
            $maxFailureRate = (float) config('ingestion.apify_gaps.max_failure_rate', 0.15);
            $maxReviewRating = (float) config('ingestion.apify_gaps.max_review_rating', 2.0);
            $minReviewCountForRating = (int) config('ingestion.apify_gaps.min_review_count_for_rating_signal', 3);

            $failureFlag = $totalRuns >= $minRuns && $failureRate >= $maxFailureRate;
            $reviewFlag = $reviewCount >= $minReviewCountForRating && $reviewRating > 0 && $reviewRating <= $maxReviewRating;

            if (! $failureFlag && ! $reviewFlag) {
                $stats['skipped'] = 1;
                $stats['duration_ms'] = (int) ((microtime(true) - $startedAt) * 1000);
                $ingestionService->finishRun($run, $stats);
                return;
            }

            $reasons = [];
            if ($failureFlag) {
                $reasons[] = sprintf('%.0f%% of %d runs in the last 30 days failed, aborted, or timed out', $failureRate * 100, $totalRuns);
            }
            if ($reviewFlag) {
                $reasons[] = sprintf('%.1f★ average across %d reviews', $reviewRating, $reviewCount);
            }

            $title = $info['title'] ?? $this->actorId;
            $categories = $info['categories'] ?? [];

            $inserted = $ingestionService->insertSignal([
                'source' => 'apify_actor_gaps',
                'source_id' => $this->actorId . ':' . now()->format('Y-\WW'),
                'source_url' => 'https://apify.com/' . $this->actorId,
                'title' => "Struggling actor: {$title}",
                'content' => "Actor {$this->actorId} shows signs of not meeting demand: " . implode('; ', $reasons) . '.',
                'score' => (int) round($failureRate * 100),
                'comment_count' => $reviewCount,
                'category' => $categories ? implode(',', $categories) : $this->actorId,
                'metadata' => [
                    'actor_id' => $this->actorId,
                    'run_stats_30d' => $runStats,
                    'failure_rate' => round($failureRate, 3),
                    'review_count' => $reviewCount,
                    'review_rating' => $reviewRating,
                ],
                'published_at' => now(),
            ], $run->id);

            $stats['inserted'] = $inserted ? 1 : 0;
            $stats['skipped'] = $inserted ? 0 : 1;
            $stats['duration_ms'] = (int) ((microtime(true) - $startedAt) * 1000);
            $ingestionService->finishRun($run, $stats);

        } catch (Throwable $e) {
            Log::error('Apify actor gap check failed', [
                'actor_id' => $this->actorId,
                'error' => $e->getMessage(),
            ]);
            $ingestionService->finishRun($run, [
                'found' => 0, 'inserted' => 0, 'skipped' => 0,
                'status' => 'failed', 'error' => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        }
    }
}
