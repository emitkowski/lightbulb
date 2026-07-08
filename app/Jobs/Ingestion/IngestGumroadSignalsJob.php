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

class IngestGumroadSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $searchTerm) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('gumroad', $this->searchTerm);

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
            $actorId = config('ingestion.apify.gumroad.actor_id', 'muhammetakkurtt/gumroad-scraper');
            $minRatingCount = config('ingestion.apify.gumroad.min_rating_count', 20);

            $items = $apifyService->runSync($actorId, [
                'searchQueries' => [$this->searchTerm],
                'maxItems' => 50,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;

            foreach ($items as $item) {
                $url = $item['url'] ?? null;
                $title = $item['name'] ?? null;
                $ratingCount = (int) ($item['ratings']['count'] ?? 0);

                if (! $url || ! $title || $ratingCount < $minRatingCount) {
                    $skipped++;
                    continue;
                }

                $sourceId = $item['id'] ?? substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'gumroad',
                    'source_id' => (string) $sourceId,
                    'source_url' => $url,
                    'title' => $title,
                    'content' => $item['description'] ?? '',
                    'author' => $item['seller']['name'] ?? null,
                    'score' => $ratingCount,
                    'comment_count' => $ratingCount,
                    'category' => $this->searchTerm,
                    'metadata' => [
                        'search_term' => $this->searchTerm,
                        'rating_count' => $ratingCount,
                        'rating_average' => $item['ratings']['average'] ?? null,
                        'price_cents' => $item['price_cents'] ?? null,
                        'currency_code' => $item['currency_code'] ?? null,
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
            Log::error('Gumroad ingestion failed', [
                'search_term' => $this->searchTerm,
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
