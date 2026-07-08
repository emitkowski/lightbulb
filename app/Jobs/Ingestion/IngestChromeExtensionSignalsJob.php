<?php

namespace App\Jobs\Ingestion;

use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IngestChromeExtensionSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $category) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('chrome_webstore', $this->category);

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
            $actorId = config('ingestion.apify.chrome.actor_id', 'vujeen/chrome-web-store-scraper');
            $minInstalls = config('ingestion.apify.chrome.min_install_count', 10000);
            $maxRating = config('ingestion.apify.chrome.max_star_rating', 4.0);
            $maxItems = config('ingestion.apify.chrome.max_items_per_category', 50);

            $items = $apifyService->runSync($actorId, [
                'searchQueries' => [$this->category],
                'maxResultsPerQuery' => $maxItems,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;

            foreach ($items as $item) {
                $url = $item['url'] ?? $item['extensionUrl'] ?? null;
                $installCount = (int) preg_replace('/[^0-9]/', '', (string) ($item['installCount'] ?? $item['users'] ?? 0));
                $rating = (float) ($item['rating'] ?? $item['averageRating'] ?? 5.0);
                $ratingCount = (int) ($item['ratingCount'] ?? $item['reviewCount'] ?? 0);
                $name = $item['name'] ?? $item['title'] ?? null;

                if (! $url || ! $name) {
                    $skipped++;
                    continue;
                }

                if ($installCount < $minInstalls || $rating > $maxRating) {
                    $skipped++;
                    continue;
                }

                // Extract extension ID from URL: .../detail/name/{extensionId}
                $extId = basename(parse_url($url, PHP_URL_PATH) ?? '');
                $sourceId = $extId ?: substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'chrome_webstore',
                    'source_id' => $sourceId,
                    'source_url' => $url,
                    'title' => $name,
                    'content' => $item['description'] ?? $item['shortDescription'] ?? '',
                    'author' => $item['developer'] ?? $item['author'] ?? null,
                    'score' => $installCount,
                    'comment_count' => $ratingCount,
                    'category' => $this->category,
                    'metadata' => [
                        'extension_id' => $extId,
                        'install_count' => $installCount,
                        'rating' => $rating,
                        'rating_count' => $ratingCount,
                        'chrome_category' => $this->category,
                        'version' => $item['version'] ?? null,
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

        } catch (\Throwable $e) {
            Log::error('Chrome extension ingestion failed', [
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
}
