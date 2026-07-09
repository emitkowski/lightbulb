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

class IngestDevToSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Articles about building something because a gap existed — explicit validation signal
    private const GAP_KEYWORDS = [
        'i built', 'we built', 'built because', "couldn't find",
        'because nothing', 'alternative to', 'launched because',
        'nothing existed', 'sick of', 'i wish', 'tired of paying',
        '$1k mrr', 'first customers', 'first revenue', 'replace',
        'does anyone know', 'is there a tool',
    ];

    public function __construct(protected string $tag) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('devto', $this->tag);

        try {
            $minReactions = config('ingestion.devto.min_reactions', 5);
            $maxAgeDays = config('ingestion.devto.max_age_days', 90);
            $perPage = config('ingestion.devto.per_page', 50);
            $cutoff = now()->subDays($maxAgeDays);

            $response = Http::withHeaders(['User-Agent' => 'Lightbulb/1.0'])
                ->timeout(15)
                ->get('https://dev.to/api/articles', [
                    'tag' => $this->tag,
                    'per_page' => $perPage,
                ]);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "Dev.to API error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $articles = $response->json() ?? [];
            $stats['found'] = count($articles);

            foreach ($articles as $article) {
                $publishedAt = Carbon::parse($article['published_at'] ?? now()->toIso8601String());

                if ($publishedAt->isBefore($cutoff)) {
                    $stats['skipped']++;
                    continue;
                }

                if (($article['positive_reactions_count'] ?? 0) < $minReactions) {
                    $stats['skipped']++;
                    continue;
                }

                $title = $article['title'] ?? '';
                $description = $article['description'] ?? '';

                if (! $this->hasGapKeyword($title) && ! $this->hasGapKeyword($description)) {
                    $stats['skipped']++;
                    continue;
                }

                $inserted = $ingestionService->insertSignal([
                    'source' => 'devto',
                    'source_id' => (string) ($article['id'] ?? null),
                    'source_url' => $article['url'] ?? null,
                    'title' => $title,
                    'content' => $description ?: $title,
                    'author' => $article['user']['username'] ?? null,
                    'score' => $article['positive_reactions_count'] ?? 0,
                    'comment_count' => $article['comments_count'] ?? 0,
                    'category' => $this->tag,
                    'metadata' => [
                        'tag' => $this->tag,
                        'tags' => $article['tag_list'] ?? [],
                        'reading_time_minutes' => $article['reading_time_minutes'] ?? null,
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

        } catch (Throwable $e) {
            Log::error('Dev.to ingestion failed', [
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
