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
 * Layer 6 — job postings as a lagging demand signal, LinkedIn side. Same rationale
 * as IngestIndeedSignalsJob: a manual-process requirement in a job description is a
 * gap signal. Monitored spot-check monthly per the spec (noisier than Indeed).
 */
class IngestLinkedInJobsSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $query) {}

    public function handle(IngestionService $ingestionService, ApifyService $apifyService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('linkedin', $this->query);

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
            $actorId = config('ingestion.apify.linkedin.actor_id', 'curious_coder/linkedin-jobs-scraper');
            $maxAgeDays = config('ingestion.apify.linkedin.max_age_days', 14);
            $count = config('ingestion.apify.linkedin.count', 25);

            $searchUrl = 'https://www.linkedin.com/jobs/search/?keywords=' . urlencode($this->query);

            $items = $apifyService->runSync($actorId, [
                'urls' => [$searchUrl],
                'count' => $count,
            ]);

            $found = count($items);
            $inserted = 0;
            $skipped = 0;
            $cutoff = now()->subDays($maxAgeDays);

            foreach ($items as $item) {
                $url = $item['link'] ?? $item['url'] ?? $item['jobUrl'] ?? null;
                $title = $item['title'] ?? null;
                $description = $item['descriptionText'] ?? $item['description'] ?? '';
                $company = $item['companyName'] ?? $item['company'] ?? null;

                if (! $url || ! $title) {
                    $skipped++;
                    continue;
                }

                $postedAt = isset($item['postedAt'])
                    ? Carbon::parse($item['postedAt'])
                    : (isset($item['listedAt']) ? Carbon::parse($item['listedAt']) : null);

                if ($postedAt && $postedAt->lt($cutoff)) {
                    $skipped++;
                    continue;
                }

                $sourceId = $item['id'] ?? $item['jobId'] ?? substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'linkedin',
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
            Log::error('LinkedIn ingestion failed', [
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
