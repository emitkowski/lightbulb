<?php

namespace App\Jobs\Ingestion;

use App\Services\Ingestion\IngestionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ingests the public LaraJobs RSS feed. Unlike other Layer 6b sources this needs
 * no Apify actor and no auth — larajobs.com/feed is a plain public RSS feed.
 */
class IngestLaraJobsSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $feedUrl = config('ingestion.larajobs.feed_url', 'https://larajobs.com/feed');
        $run = $ingestionService->startRun('larajobs', $feedUrl);

        try {
            $response = Http::timeout(15)->get($feedUrl);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "LaraJobs feed error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $maxAgeDays = config('ingestion.larajobs.max_age_days', 14);
            $cutoff = now()->subDays($maxAgeDays);

            $xml = @simplexml_load_string($response->body());

            if ($xml === false) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => 'LaraJobs feed could not be parsed as XML',
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $items = $xml->channel->item ?? [];
            $stats['found'] = count($items);

            foreach ($items as $item) {
                $jobFields = $item->children('job', true);

                $title = (string) $item->title ?: null;
                $link = (string) $item->link ?: null;
                $guid = (string) $item->guid ?: $link;

                if (! $guid || ! $title) {
                    $stats['skipped']++;
                    continue;
                }

                $publishedAt = $item->pubDate ? Carbon::parse((string) $item->pubDate) : now();

                if ($publishedAt->isBefore($cutoff)) {
                    $stats['skipped']++;
                    continue;
                }

                $tags = (string) ($jobFields->tags ?? '');
                $location = (string) ($jobFields->location ?? '');
                $jobType = (string) ($jobFields->job_type ?? '');
                $company = (string) ($jobFields->company ?? $item->children('dc', true)->creator ?? '');

                $content = trim(implode(' — ', array_filter([
                    $title,
                    $jobType ? "Type: {$jobType}" : null,
                    $location ? "Location: {$location}" : null,
                    $tags ? "Tags: {$tags}" : null,
                ])));

                $sourceId = substr(md5($guid), 0, 16);

                $inserted = $ingestionService->insertSignal([
                    'source' => 'larajobs',
                    'source_id' => $sourceId,
                    'source_url' => $link,
                    'title' => $title,
                    'content' => $content,
                    'author' => $company ?: null,
                    'score' => 0,
                    'comment_count' => 0,
                    'category' => $jobType ?: null,
                    'metadata' => [
                        'location' => $location ?: null,
                        'job_type' => $jobType ?: null,
                        'tags' => $tags ? array_map('trim', explode(',', $tags)) : [],
                    ],
                    'published_at' => $publishedAt,
                ], $run->id);

                if ($inserted) {
                    $stats['inserted']++;
                } else {
                    $stats['skipped']++;
                }
            }

            $stats['duration_ms'] = (int) ((microtime(true) - $startedAt) * 1000);
            $ingestionService->finishRun($run, $stats);

        } catch (\Throwable $e) {
            Log::error('LaraJobs ingestion failed', ['error' => $e->getMessage()]);
            $ingestionService->finishRun($run, [
                'found' => 0, 'inserted' => 0, 'skipped' => 0,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        }
    }
}
