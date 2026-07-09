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

class IngestGitHubIssuesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $repo) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('github_issues', $this->repo);

        try {
            $minReactions = config('ingestion.github.min_reactions', 20);
            $minAgeDays = config('ingestion.github.min_age_days', 180);
            $maxPerRepo = config('ingestion.github.max_per_repo', 100);
            $cutoff = now()->subDays($minAgeDays);

            $request = Http::withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28'])
                ->timeout(15);

            $token = config('ingestion.github.token');
            if ($token) {
                $request = $request->withToken($token);
            }

            $response = $request->get('https://api.github.com/search/issues', [
                'q' => "is:issue is:open reactions:>={$minReactions} repo:{$this->repo}",
                'sort' => 'reactions',
                'order' => 'desc',
                'per_page' => min($maxPerRepo, 100),
            ]);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "GitHub API error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $items = $response->json('items', []);
            $stats['found'] = count($items);

            foreach ($items as $issue) {
                $createdAt = Carbon::parse($issue['created_at'] ?? now()->toIso8601String());
                $thumbsUp = $issue['reactions']['+1'] ?? 0;

                if ($thumbsUp < $minReactions || $createdAt->isAfter($cutoff)) {
                    $stats['skipped']++;
                    continue;
                }

                $inserted = $ingestionService->insertSignal([
                    'source' => 'github_issues',
                    'source_id' => "{$this->repo}#{$issue['number']}",
                    'source_url' => $issue['html_url'] ?? null,
                    'title' => $issue['title'] ?? null,
                    'content' => $issue['body'] ?? ($issue['title'] ?? ''),
                    'author' => $issue['user']['login'] ?? null,
                    'score' => $thumbsUp,
                    'comment_count' => $issue['comments'] ?? 0,
                    'category' => $this->repo,
                    'metadata' => [
                        'repo' => $this->repo,
                        'issue_number' => $issue['number'] ?? null,
                        'labels' => array_map(fn ($l) => $l['name'], $issue['labels'] ?? []),
                        'total_reactions' => $issue['reactions']['total_count'] ?? 0,
                        'age_days' => (int) $createdAt->diffInDays(now()),
                    ],
                    'published_at' => $createdAt,
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
            Log::error('GitHub Issues ingestion failed', [
                'repo' => $this->repo,
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
