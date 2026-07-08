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
use Illuminate\Support\Str;

class IngestProductHuntSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const GRAPHQL_URL = 'https://api.producthunt.com/v2/api/graphql';

    private const GAP_KEYWORDS = [
        'missing', 'wish it had', 'would buy if', "doesn't do",
        "doesn't have", 'waiting for', 'almost signed up',
        'would sign up', 'if only', 'lacks', 'needs to add',
        'please add', 'would love if', 'not yet', 'no support for',
    ];

    public function __construct(protected string $topic) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('producthunt', $this->topic);

        try {
            $apiKey = config('ingestion.producthunt.api_key');

            if (! $apiKey) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => 'PRODUCTHUNT_API_KEY is not configured',
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $minVotes = config('ingestion.producthunt.min_comment_votes', 3);
            $maxAgeDays = config('ingestion.producthunt.max_age_days', 30);
            $maxPosts = config('ingestion.producthunt.max_posts_per_topic', 20);
            $maxComments = config('ingestion.producthunt.max_comments_per_post', 30);

            $postedAfter = now()->subDays($maxAgeDays)->toIso8601String();

            $query = <<<'GQL'
            query FetchPosts($topic: String!, $postedAfter: DateTime!, $maxPosts: Int!, $maxComments: Int!) {
              posts(first: $maxPosts, topic: $topic, postedAfter: $postedAfter, order: VOTES) {
                edges {
                  node {
                    id
                    name
                    tagline
                    url
                    votesCount
                    createdAt
                    comments(first: $maxComments, order: VOTES_COUNT) {
                      edges {
                        node {
                          id
                          body
                          votesCount
                          createdAt
                        }
                      }
                    }
                  }
                }
              }
            }
            GQL;

            $response = Http::withToken($apiKey)
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->timeout(20)
                ->post(self::GRAPHQL_URL, [
                    'query' => $query,
                    'variables' => [
                        'topic' => $this->topic,
                        'postedAfter' => $postedAfter,
                        'maxPosts' => $maxPosts,
                        'maxComments' => $maxComments,
                    ],
                ]);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "Product Hunt API error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $errors = $response->json('errors');
            if ($errors) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => 'GraphQL error: ' . ($errors[0]['message'] ?? 'unknown'),
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $posts = $response->json('data.posts.edges', []);

            foreach ($posts as $postEdge) {
                $post = $postEdge['node'] ?? [];
                $comments = $post['comments']['edges'] ?? [];

                foreach ($comments as $commentEdge) {
                    $comment = $commentEdge['node'] ?? [];
                    $stats['found']++;

                    if (($comment['votesCount'] ?? 0) < $minVotes) {
                        $stats['skipped']++;
                        continue;
                    }

                    $body = $comment['body'] ?? '';
                    if (! $this->hasGapKeyword($body)) {
                        $stats['skipped']++;
                        continue;
                    }

                    $inserted = $ingestionService->insertSignal([
                        'source' => 'producthunt',
                        'source_id' => (string) ($comment['id'] ?? null),
                        'source_url' => $post['url'] ?? null,
                        'title' => Str::limit($post['name'] ?? '', 200),
                        'content' => $body,
                        'author' => null,
                        'score' => $comment['votesCount'] ?? 0,
                        'comment_count' => 0,
                        'category' => $this->topic,
                        'metadata' => [
                            'topic' => $this->topic,
                            'post_id' => $post['id'] ?? null,
                            'post_name' => $post['name'] ?? null,
                            'post_tagline' => $post['tagline'] ?? null,
                            'post_votes' => $post['votesCount'] ?? 0,
                        ],
                        'published_at' => Carbon::parse($comment['createdAt'] ?? now()->toIso8601String()),
                    ], $run->id);

                    if ($inserted) {
                        $stats['inserted']++;
                    } else {
                        $stats['skipped']++;
                    }
                }
            }

            $stats['duration_ms'] = (int) ((microtime(true) - $startedAt) * 1000);
            $ingestionService->finishRun($run, $stats);

        } catch (\Throwable $e) {
            Log::error('Product Hunt ingestion failed', [
                'topic' => $this->topic,
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

    private function hasGapKeyword(string $text): bool
    {
        $lower = strtolower($text);
        foreach (self::GAP_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
