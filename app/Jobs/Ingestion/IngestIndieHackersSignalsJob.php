<?php

namespace App\Jobs\Ingestion;

use Throwable;
use App\Services\Ingestion\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IngestIndieHackersSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $query) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('indiehackers', $this->query);

        try {
            $apiKey = config('scoring.serper.api_key');

            if (! $apiKey) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => 'SERPER_API_KEY is not configured',
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $resultsPerQuery = config('ingestion.serper.indiehackers.results_per_query', 10);
            $searchQuery = "site:indiehackers.com \"{$this->query}\"";

            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post(config('scoring.serper.base_url') . '/search', [
                    'q' => $searchQuery,
                    'num' => $resultsPerQuery,
                ]);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "Serper API returned {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $organic = $response->json('organic', []);
            $found = count($organic);
            $inserted = 0;
            $skipped = 0;

            foreach ($organic as $position => $result) {
                $url = $result['link'] ?? null;
                if (! $url) {
                    $skipped++;
                    continue;
                }

                // Deliberately not query-prefixed (unlike the per-tool Serper jobs):
                // the same IH post surfacing under two different query phrases is
                // still the same content, so it should dedupe to one signal.
                $sourceId = substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'indiehackers',
                    'source_id' => $sourceId,
                    'source_url' => $url,
                    'title' => $result['title'] ?? $this->query,
                    'content' => $result['snippet'] ?? '',
                    'author' => null,
                    'score' => max(0, $resultsPerQuery - $position),
                    'comment_count' => 0,
                    'category' => $this->query,
                    'metadata' => [
                        'query' => $this->query,
                        'search_query' => $searchQuery,
                        'position' => $position + 1,
                        'domain' => parse_url($url, PHP_URL_HOST),
                    ],
                    'published_at' => now(),
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

        } catch (Throwable $e) {
            Log::error('Indie Hackers ingestion failed', [
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
