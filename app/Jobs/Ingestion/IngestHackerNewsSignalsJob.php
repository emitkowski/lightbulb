<?php

namespace App\Jobs\Ingestion;

use Throwable;
use App\Services\Ingestion\IngestionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IngestHackerNewsSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $query) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('hackernews', $this->query);

        try {
            $maxAgeDays = config('ingestion.hackernews.max_age_days', 30);
            $cutoffTimestamp = now()->subDays($maxAgeDays)->timestamp;
            $baseUrl = config('ingestion.hackernews.base_url', 'https://hn.algolia.com/api/v1');
            $minPointsAsk = config('ingestion.hackernews.min_points_ask', 50);

            $response = Http::timeout(15)
                ->get("{$baseUrl}/search", [
                    'query' => $this->query,
                    'tags' => '(ask_hn,show_hn)',
                    'numericFilters' => "created_at_i>{$cutoffTimestamp}",
                    'hitsPerPage' => 50,
                ]);

            if (! $response->successful()) {
                $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'failed', 'error' => "HN API error: {$response->status()}", 'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000)];
                $ingestionService->finishRun($run, $stats);
                return;
            }

            $hits = $response->json('hits', []);
            $stats['found'] = count($hits);

            $minPointsShow = config('ingestion.hackernews.min_points_show', 100);

            foreach ($hits as $hit) {
                $tags = $hit['_tags'] ?? [];
                $isShowHn = in_array('show_hn', $tags);
                $minPoints = $isShowHn ? $minPointsShow : $minPointsAsk;

                if (($hit['points'] ?? 0) < $minPoints) {
                    $stats['skipped']++;
                    continue;
                }

                $category = in_array('ask_hn', $tags) ? 'ask_hn' : (in_array('show_hn', $tags) ? 'show_hn' : ($tags[0] ?? 'story'));

                $inserted = $ingestionService->insertSignal([
                    'source' => 'hackernews',
                    'source_id' => $hit['objectID'] ?? null,
                    'source_url' => isset($hit['objectID']) ? 'https://news.ycombinator.com/item?id=' . $hit['objectID'] : null,
                    'title' => $hit['title'] ?? null,
                    'content' => $hit['story_text'] ?: ($hit['title'] ?? ''),
                    'author' => $hit['author'] ?? null,
                    'score' => $hit['points'] ?? 0,
                    'comment_count' => $hit['num_comments'] ?? 0,
                    'category' => $category,
                    'metadata' => [
                        'url' => $hit['url'] ?? null,
                        '_tags' => $tags,
                    ],
                    'published_at' => Carbon::createFromTimestamp($hit['created_at_i'] ?? now()->timestamp),
                ], $run->id);

                if ($inserted) {
                    $stats['inserted']++;
                } else {
                    $stats['skipped']++;
                }
            }

            $stats['duration_ms'] = (int) ((microtime(true) - $startedAt) * 1000);
            $ingestionService->finishRun($run, $stats);

        } catch (Throwable $e) {
            Log::error('HackerNews ingestion failed', [
                'query' => $this->query,
                'error' => $e->getMessage(),
            ]);
            $ingestionService->finishRun($run, [
                'found' => 0,
                'inserted' => 0,
                'skipped' => 0,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        }
    }
}
