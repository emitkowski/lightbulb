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

class IngestStackOverflowSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Pre-created filter that includes: answer_count, body, creation_date,
    // is_answered, link, question_id, score, tags, title, view_count
    private const FILTER = '!9_bDE(fI5';

    public function __construct(protected string $tag) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('stackoverflow', $this->tag);

        try {
            $minScore = config('ingestion.stackoverflow.min_score', 10);
            $minViewCount = config('ingestion.stackoverflow.min_view_count', 500);
            $minAgeDays = config('ingestion.stackoverflow.min_age_days', 365);
            $maxPerTag = config('ingestion.stackoverflow.max_per_tag', 100);

            // Only questions older than min_age_days — genuinely unsolved, not just recent
            $toDate = now()->subDays($minAgeDays)->timestamp;

            $params = [
                'accepted' => 'false',
                'tagged' => $this->tag,
                'sort' => 'votes',
                'min' => $minScore,
                'site' => 'stackoverflow',
                'todate' => $toDate,
                'filter' => self::FILTER,
                'pagesize' => min($maxPerTag, 100),
                'order' => 'desc',
            ];

            $key = config('ingestion.stackoverflow.key');
            if ($key) {
                $params['key'] = $key;
            }

            $response = Http::timeout(15)
                ->get('https://api.stackexchange.com/2.3/search/advanced', $params);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "StackExchange API error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $items = $response->json('items', []);
            $stats['found'] = count($items);

            foreach ($items as $question) {
                if (($question['view_count'] ?? 0) < $minViewCount) {
                    $stats['skipped']++;
                    continue;
                }

                $createdAt = Carbon::createFromTimestamp($question['creation_date'] ?? now()->timestamp);

                $inserted = $ingestionService->insertSignal([
                    'source' => 'stackoverflow',
                    'source_id' => (string) ($question['question_id'] ?? null),
                    'source_url' => $question['link'] ?? null,
                    'title' => $question['title'] ?? null,
                    'content' => isset($question['body'])
                        ? strip_tags($question['body'])
                        : ($question['title'] ?? ''),
                    'author' => null,
                    'score' => $question['score'] ?? 0,
                    'comment_count' => $question['answer_count'] ?? 0,
                    'category' => $this->tag,
                    'metadata' => [
                        'tag' => $this->tag,
                        'view_count' => $question['view_count'] ?? 0,
                        'answer_count' => $question['answer_count'] ?? 0,
                        'is_answered' => $question['is_answered'] ?? false,
                        'tags' => $question['tags'] ?? [],
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
            Log::error('Stack Overflow ingestion failed', [
                'tag' => $this->tag,
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
