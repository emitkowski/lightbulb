<?php

namespace App\Jobs\Ingestion;

use App\Services\Ingestion\ApifyService;
use App\Services\Ingestion\IngestionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Layer 6 — job postings as a lagging demand signal. A job description that says
 * "responsible for manually compiling weekly client reports" is a gap signal:
 * a company is paying a salary for work software should be doing.
 */
class IngestIndeedSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $query) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('indeed', $this->query);

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
            $actorId = config('ingestion.apify.indeed.actor_id', 'misceres/indeed-scraper');
            $country = config('ingestion.apify.indeed.country', 'US');
            $maxAgeDays = config('ingestion.apify.indeed.max_age_days', 14);
            $maxItems = config('ingestion.apify.indeed.max_items_per_search', 25);

            $items = $apifyService->runSync($actorId, [
                'position' => $this->query,
                'country' => $country,
                'maxItemsPerSearch' => $maxItems,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;
            $cutoff = now()->subDays($maxAgeDays);

            foreach ($items as $item) {
                $url = $item['url'] ?? $item['link'] ?? null;
                $title = $item['positionName'] ?? $item['title'] ?? null;
                $description = $item['description'] ?? $item['snippet'] ?? '';
                $company = $item['company'] ?? $item['companyName'] ?? null;

                if (! $url || ! $title) {
                    $skipped++;
                    continue;
                }

                $postedAt = isset($item['postingDateParsed'])
                    ? Carbon::parse($item['postingDateParsed'])
                    : (isset($item['postedAt']) ? Carbon::parse($item['postedAt']) : null);

                if ($postedAt && $postedAt->lt($cutoff)) {
                    $skipped++;
                    continue;
                }

                $sourceId = $item['id'] ?? $item['jobKey'] ?? substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'indeed',
                    'source_id' => (string) $sourceId,
                    'source_url' => $url,
                    'title' => $title,
                    'content' => $description,
                    'author' => $company,
                    'score' => 0,
                    'comment_count' => 0,
                    'category' => $this->query,
                    'metadata' => [
                        'search_query' => $this->query,
                        'location' => $item['location'] ?? null,
                        'salary' => $item['salary'] ?? null,
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
            Log::error('Indeed ingestion failed', [
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
