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

class IngestFreelancePlatformsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $query) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('freelance', $this->query);

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
            $actorId = config('ingestion.apify.freelance.actor_id', 'getdataforme/upwork-actor');
            $minBudget = config('ingestion.apify.freelance.min_budget', 500);
            $maxAgeDays = config('ingestion.apify.freelance.max_age_days', 7);
            $itemLimit = config('ingestion.apify.freelance.item_limit', 50);

            $items = $apifyService->runSync($actorId, [
                'queries' => [$this->query],
                'item_limit' => $itemLimit,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;
            $cutoff = now()->subDays($maxAgeDays);

            foreach ($items as $item) {
                $url = $item['url'] ?? $item['jobUrl'] ?? null;
                $title = $item['title'] ?? $item['jobTitle'] ?? null;
                $description = $item['description'] ?? $item['jobDescription'] ?? null;

                // Budget: may be a range string "500-1000" or a number
                $rawBudget = $item['budget'] ?? $item['fixedPrice'] ?? $item['hourlyRate'] ?? 0;
                $budget = is_string($rawBudget)
                    ? (int) preg_replace('/[^0-9]/', '', explode('-', $rawBudget)[0])
                    : (int) $rawBudget;

                if (! $url || ! $title || $budget < $minBudget) {
                    $skipped++;
                    continue;
                }

                $postedAt = isset($item['postedAt'])
                    ? \Carbon\Carbon::parse($item['postedAt'])
                    : null;

                if ($postedAt && $postedAt->lt($cutoff)) {
                    $skipped++;
                    continue;
                }

                $sourceId = substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'freelance',
                    'source_id' => $sourceId,
                    'source_url' => $url,
                    'title' => $title,
                    'content' => $description ?? '',
                    'author' => $item['clientCountry'] ?? null,
                    'score' => $budget,
                    'comment_count' => (int) ($item['proposalCount'] ?? $item['bids'] ?? 0),
                    'category' => $this->query,
                    'metadata' => [
                        'search_query' => $this->query,
                        'budget' => $budget,
                        'budget_type' => $item['budgetType'] ?? (($item['hourlyRate'] ?? null) ? 'hourly' : 'fixed'),
                        'skills' => $item['skills'] ?? [],
                        'platform' => 'upwork',
                        'posted_at' => $item['postedAt'] ?? null,
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

        } catch (\Throwable $e) {
            Log::error('Freelance platforms ingestion failed', [
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
