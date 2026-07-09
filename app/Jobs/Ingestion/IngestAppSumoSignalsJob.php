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

class IngestAppSumoSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const GAP_KEYWORDS = [
        'missing', 'wish', "doesn't have", "doesn't do", 'would be perfect if',
        'waiting for', 'needs to add', 'lack', 'no support', 'hope they add',
        'please add', 'need them to', 'should have', 'would pay for',
        "can't do", 'no way to', 'wish it', 'limited', 'no integration',
    ];

    public function __construct(protected string $category) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('appsumo', $this->category);

        if (! $apifyService->hasToken()) {
            $ingestionService->finishRun($run, [
                'found' => 0, 'inserted' => 0, 'skipped' => 0,
                'status' => 'failed',
                'error' => 'APIFY_TOKEN is not configured',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
            return;
        }

        try {
            $actorId = config('ingestion.apify.appsumo.actor_id', 'shahidirfan/appsumo-scraper');
            $maxReviews = config('ingestion.apify.appsumo.max_reviews_per_category', 100);
            $maxStarRating = config('ingestion.apify.appsumo.max_star_rating', 4);
            $minReviewCount = config('ingestion.apify.appsumo.min_review_count', 50);

            $items = $apifyService->runSync($actorId, [
                'keyword' => str_replace('-', ' ', $this->category),
                'results_wanted' => $maxReviews,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;

            foreach ($items as $item) {
                // AppSumo actor may return product-level or review-level items
                $isReview = isset($item['reviewBody']) || isset($item['reviewText']);

                if ($isReview) {
                    $this->processReview($item, $maxStarRating, $ingestionService, $run->id, $inserted, $skipped);
                } else {
                    $this->processProduct($item, $minReviewCount, $maxStarRating, $ingestionService, $run->id, $inserted, $skipped);
                }
            }

            $ingestionService->finishRun($run, [
                'found' => $found,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'status' => 'success',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

        } catch (Throwable $e) {
            Log::error('AppSumo ingestion failed', [
                'category' => $this->category,
                'error' => $e->getMessage(),
            ]);
            $ingestionService->finishRun($run, [
                'found' => 0, 'inserted' => 0, 'skipped' => 0,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        }
    }

    /**
     * Store a product as a signal when it has enough reviews and a below-average rating
     * — high engagement + mediocre satisfaction = market gap candidate.
     */
    private function processProduct(
        array $item,
        int $minReviewCount,
        int $maxStarRating,
        IngestionService $ingestionService,
        string $runId,
        int &$inserted,
        int &$skipped
    ): void {
        $url = $item['url'] ?? $item['productUrl'] ?? null;
        $title = $item['title'] ?? $item['name'] ?? null;
        $reviewCount = (int) ($item['reviewCount'] ?? $item['ratingCount'] ?? $item['review_count'] ?? 0);
        $avgRating = (float) ($item['avgRating'] ?? $item['rating'] ?? 5.0);

        if (! $url || ! $title || $reviewCount < $minReviewCount || $avgRating > $maxStarRating) {
            $skipped++;
            return;
        }

        $sourceId = 'product:' . substr(md5($url), 0, 12);

        $wasInserted = $ingestionService->insertSignal([
            'source' => 'appsumo',
            'source_id' => $sourceId,
            'source_url' => $url,
            'title' => $title,
            'content' => $item['description'] ?? $item['tagline'] ?? $item['description_text'] ?? '',
            'author' => null,
            'score' => $reviewCount,
            'comment_count' => $reviewCount,
            'category' => $this->category,
            'metadata' => [
                'appsumo_category' => $this->category,
                'avg_rating' => $avgRating,
                'review_count' => $reviewCount,
                'price' => $item['price'] ?? null,
                'type' => 'product',
            ],
            'published_at' => now(),
        ], $runId);

        if ($wasInserted) {
            $inserted++;
        } else {
            $skipped++;
        }
    }

    private function processReview(
        array $item,
        int $maxStarRating,
        IngestionService $ingestionService,
        string $runId,
        int &$inserted,
        int &$skipped
    ): void {
        $reviewText = $item['reviewBody'] ?? $item['reviewText'] ?? null;
        $rating = (int) ($item['rating'] ?? $item['stars'] ?? 0);
        $url = $item['url'] ?? $item['reviewUrl'] ?? null;

        if (! $reviewText || ! $url || $rating > $maxStarRating) {
            $skipped++;
            return;
        }

        if (! $this->hasGapKeyword($reviewText)) {
            $skipped++;
            return;
        }

        $sourceId = 'review:' . substr(md5($url . $reviewText), 0, 12);

        $wasInserted = $ingestionService->insertSignal([
            'source' => 'appsumo',
            'source_id' => $sourceId,
            'source_url' => $url,
            'title' => $item['reviewTitle'] ?? "AppSumo {$rating}-star review",
            'content' => $reviewText,
            'author' => $item['reviewer'] ?? $item['reviewerName'] ?? null,
            'score' => $rating,
            'comment_count' => 0,
            'category' => $this->category,
            'metadata' => [
                'appsumo_category' => $this->category,
                'rating' => $rating,
                'type' => 'review',
                'product_name' => $item['productName'] ?? null,
            ],
            'published_at' => now(),
        ], $runId);

        if ($wasInserted) {
            $inserted++;
        } else {
            $skipped++;
        }
    }

    private function hasGapKeyword(string $text): bool
    {
        $lower = strtolower($text);
        foreach (self::GAP_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
