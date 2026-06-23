<?php

namespace App\Jobs\Ingestion;

use App\Models\IngestionRun;
use App\Services\Ingestion\IngestionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IngestRedditSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $subreddit,
        protected string $query
    ) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('reddit', $this->query);

        try {
            $token = $this->getAccessToken();

            if ($token === null) {
                $ingestionService->finishRun($run, ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'failed', 'error' => 'Failed to obtain Reddit access token', 'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000)]);
                return;
            }

            $minScore = config('ingestion.reddit.min_score', 10);
            $maxAgeDays = config('ingestion.reddit.max_age_days', 7);
            $cutoff = now()->subDays($maxAgeDays);

            $response = Http::withToken($token)
                ->withUserAgent(config('ingestion.reddit.user_agent'))
                ->timeout(15)
                ->get("https://oauth.reddit.com/r/{$this->subreddit}/search", [
                    'q' => $this->query,
                    'sort' => 'relevance',
                    't' => 'week',
                    'limit' => 25,
                    'type' => 'link',
                ]);

            if ($response->status() === 429) {
                Log::warning('Reddit rate limit hit', ['subreddit' => $this->subreddit, 'query' => $this->query]);
                sleep(60);
                $ingestionService->finishRun($run, array_merge($stats, ['status' => 'partial', 'error' => 'Rate limited by Reddit', 'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000)]));
                return;
            }

            if (! $response->successful()) {
                $ingestionService->finishRun($run, ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'failed', 'error' => "Reddit API error: {$response->status()}", 'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000)]);
                return;
            }

            $posts = $response->json('data.children', []);
            $stats['found'] = count($posts);

            foreach ($posts as $child) {
                $post = $child['data'] ?? [];

                if (($post['score'] ?? 0) < $minScore) {
                    $stats['skipped']++;
                    continue;
                }

                $publishedAt = Carbon::createFromTimestamp($post['created_utc'] ?? now()->timestamp);
                if ($publishedAt->isBefore($cutoff)) {
                    $stats['skipped']++;
                    continue;
                }

                $inserted = $ingestionService->insertSignal([
                    'source' => 'reddit',
                    'source_id' => $post['id'] ?? null,
                    'source_url' => isset($post['permalink']) ? 'https://reddit.com' . $post['permalink'] : null,
                    'title' => $post['title'] ?? null,
                    'content' => $post['selftext'] ?: ($post['title'] ?? ''),
                    'author' => $post['author'] ?? null,
                    'score' => $post['score'] ?? 0,
                    'comment_count' => $post['num_comments'] ?? 0,
                    'category' => $this->subreddit,
                    'metadata' => [
                        'subreddit' => $post['subreddit'] ?? $this->subreddit,
                        'url' => $post['url'] ?? null,
                        'is_self' => $post['is_self'] ?? false,
                        'link_flair_text' => $post['link_flair_text'] ?? null,
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

            $sleepMs = config('ingestion.reddit.rate_limit_sleep_ms', 1000);
            usleep($sleepMs * 1000);

        } catch (\Throwable $e) {
            Log::error('Reddit ingestion failed', [
                'subreddit' => $this->subreddit,
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

    private function getAccessToken(): ?string
    {
        try {
            $response = Http::withBasicAuth(
                (string) config('ingestion.reddit.client_id', ''),
                (string) config('ingestion.reddit.client_secret', '')
            )
                ->withUserAgent((string) config('ingestion.reddit.user_agent', 'Lightbulb/1.0'))
                ->asForm()
                ->timeout(10)
                ->post('https://www.reddit.com/api/v1/access_token', [
                    'grant_type' => 'client_credentials',
                ]);

            return $response->json('access_token');
        } catch (\Throwable) {
            return null;
        }
    }
}
