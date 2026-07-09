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

class IngestTwitterSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $query) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('twitter', $this->query);

        try {
            $bearerToken = config('ingestion.twitter.bearer_token');

            if (! $bearerToken) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => 'TWITTER_BEARER_TOKEN is not configured',
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $minLikes = config('ingestion.twitter.min_likes', 5);
            $maxResults = config('ingestion.twitter.max_results', 25);

            $response = Http::withToken($bearerToken)
                ->timeout(15)
                ->get('https://api.twitter.com/2/tweets/search/recent', [
                    'query' => "\"{$this->query}\" -is:retweet lang:en",
                    'max_results' => max(10, min($maxResults, 100)),
                    'tweet.fields' => 'created_at,public_metrics,author_id',
                    'expansions' => 'author_id',
                    'user.fields' => 'username',
                ]);

            if ($response->status() === 429) {
                Log::warning('Twitter rate limit hit', ['query' => $this->query]);
                $ingestionService->finishRun($run, array_merge($stats, [
                    'status' => 'partial',
                    'error' => 'Rate limited by Twitter API',
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]));
                return;
            }

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "Twitter API error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $tweets = $response->json('data', []);
            $stats['found'] = count($tweets);

            $usersById = collect($response->json('includes.users', []))->keyBy('id');

            foreach ($tweets as $tweet) {
                $metrics = $tweet['public_metrics'] ?? [];
                $likes = $metrics['like_count'] ?? 0;

                if ($likes < $minLikes) {
                    $stats['skipped']++;
                    continue;
                }

                $authorId = $tweet['author_id'] ?? null;
                $username = $usersById->get($authorId)['username'] ?? null;

                $inserted = $ingestionService->insertSignal([
                    'source' => 'twitter',
                    'source_id' => $tweet['id'] ?? null,
                    'source_url' => isset($tweet['id']) ? "https://twitter.com/i/web/status/{$tweet['id']}" : null,
                    'title' => null,
                    'content' => $tweet['text'] ?? '',
                    'author' => $username,
                    'score' => $likes,
                    'comment_count' => $metrics['reply_count'] ?? 0,
                    'category' => $this->query,
                    'metadata' => [
                        'author_id' => $authorId,
                        'retweet_count' => $metrics['retweet_count'] ?? 0,
                        'quote_count' => $metrics['quote_count'] ?? 0,
                    ],
                    'published_at' => Carbon::parse($tweet['created_at'] ?? now()->toIso8601String()),
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
            Log::error('Twitter ingestion failed', [
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
