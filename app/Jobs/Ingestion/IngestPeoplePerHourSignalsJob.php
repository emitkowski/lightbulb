<?php

namespace App\Jobs\Ingestion;

use Throwable;
use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IngestPeoplePerHourSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $query) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('peopleperhour', $this->query);

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
            $actorId = config('ingestion.apify.peopleperhour.actor_id', 'getdataforme/peopleperhour-job-scraper');
            $minBudget = config('ingestion.apify.peopleperhour.min_budget', 200);
            $maxAgeDays = config('ingestion.apify.peopleperhour.max_age_days', 14);
            $itemLimit = config('ingestion.apify.peopleperhour.item_limit', 30);

            $items = $apifyService->runSync($actorId, [
                'queries' => [$this->query],
                'item_limit' => $itemLimit,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;
            $cutoff = now()->subDays($maxAgeDays);

            foreach ($items as $item) {
                $url = $item['url'] ?? $item['project_url'] ?? null;
                $title = $item['title'] ?? null;
                $description = $item['description'] ?? '';

                $rawBudget = $item['budget_converted'] ?? $item['budget'] ?? 0;
                $budget = is_string($rawBudget)
                    ? (int) preg_replace('/[^0-9]/', '', explode('-', $rawBudget)[0])
                    : (int) $rawBudget;

                if (! $url || ! $title || $budget < $minBudget) {
                    $skipped++;
                    continue;
                }

                $postedAt = isset($item['posted_date'])
                    ? Carbon::parse($item['posted_date'])
                    : null;

                if ($postedAt && $postedAt->lt($cutoff)) {
                    $skipped++;
                    continue;
                }

                $sourceId = $item['project_id'] ?? substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'peopleperhour',
                    'source_id' => (string) $sourceId,
                    'source_url' => $url,
                    'title' => $title,
                    'content' => $description,
                    'author' => $item['client_country'] ?? null,
                    'score' => $budget,
                    'comment_count' => (int) ($item['proposal_count'] ?? 0),
                    'category' => $item['category'] ?? $this->query,
                    'metadata' => [
                        'search_query' => $this->query,
                        'budget' => $budget,
                        'currency' => $item['currency'] ?? null,
                        'sub_category' => $item['sub_category'] ?? null,
                        'location_type' => $item['location_type'] ?? null,
                        'platform' => 'peopleperhour',
                    ],
                    'published_at' => $postedAt ?? now(),
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
            Log::error('PeoplePerHour ingestion failed', [
                'query' => $this->query,
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
