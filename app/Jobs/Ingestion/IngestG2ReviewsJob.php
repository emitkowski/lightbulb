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

class IngestG2ReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const GAP_KEYWORDS = [
        'missing', 'wish', "doesn't have", "doesn't do", 'would be perfect if',
        'waiting for', 'needs to add', 'lack', 'no support', 'hope they add',
        'please add', 'need them to', 'should have', 'would pay for',
        "can't do", 'unable to', 'no way to', 'no feature', 'wish it',
    ];

    public function __construct(protected string $category) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('g2', $this->category);

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
            $actorId = config('ingestion.apify.g2.actor_id', 'epctex/g2-scraper');
            $maxReviews = config('ingestion.apify.g2.max_reviews_per_category', 50);
            $maxStarRating = config('ingestion.apify.g2.max_star_rating', 3);

            $categoryUrl = "https://www.g2.com/categories/{$this->category}?order=most_reviews";

            $items = $apifyService->runSync($actorId, [
                'startUrls' => [['url' => $categoryUrl]],
                'maxItems' => $maxReviews,
                'includeReviews' => true,
                'maxStars' => $maxStarRating,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;

            foreach ($items as $item) {
                $reviewText = $item['reviewBody'] ?? $item['body'] ?? $item['reviewText'] ?? null;
                $reviewTitle = $item['reviewTitle'] ?? $item['title'] ?? null;
                $rating = (int) ($item['rating'] ?? $item['stars'] ?? 0);
                $productName = $item['productName'] ?? $item['product'] ?? $this->category;
                $url = $item['url'] ?? $item['reviewUrl'] ?? $categoryUrl;

                // Apply star rating filter (actor may not filter itself)
                if ($rating > $maxStarRating) {
                    $skipped++;
                    continue;
                }

                $text = ($reviewTitle ?? '') . ' ' . ($reviewText ?? '');
                if (! $this->hasGapKeyword($text)) {
                    $skipped++;
                    continue;
                }

                $sourceId = substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'g2',
                    'source_id' => $sourceId,
                    'source_url' => $url,
                    'title' => $reviewTitle ?? "G2 {$rating}-star review: {$productName}",
                    'content' => $reviewText ?? $reviewTitle ?? '',
                    'author' => $item['reviewer'] ?? $item['reviewerName'] ?? null,
                    'score' => $rating,
                    'comment_count' => 0,
                    'category' => $this->category,
                    'metadata' => [
                        'product_name' => $productName,
                        'rating' => $rating,
                        'g2_category' => $this->category,
                        'reviewer_role' => $item['role'] ?? $item['reviewerTitle'] ?? null,
                        'company_size' => $item['companySize'] ?? null,
                    ],
                    'published_at' => now(),
                ], $run->id);

                if ($wasInserted) {
                    $inserted++;
                } else {
                    $skipped++;
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
            Log::error('G2 reviews ingestion failed', [
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
