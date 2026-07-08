<?php

namespace App\Jobs\Ingestion;

use App\Services\Ingestion\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IngestAlternativesSearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $tool) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('serper_alternatives', $this->tool);

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

            $templates = config('ingestion.serper.alternatives.query_templates', [
                'alternatives to {tool}',
                '{tool} alternative',
                '{tool} competitor',
            ]);
            $resultsPerQuery = config('ingestion.serper.alternatives.results_per_query', 10);

            foreach ($templates as $template) {
                $query = str_replace('{tool}', $this->tool, $template);

                $response = Http::withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(15)
                    ->post(config('scoring.serper.base_url') . '/search', [
                        'q' => $query,
                        'num' => $resultsPerQuery,
                    ]);

                if (! $response->successful()) {
                    Log::warning('Serper alternatives search failed', [
                        'tool' => $this->tool,
                        'query' => $query,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                $organic = $response->json('organic', []);
                $stats['found'] += count($organic);

                foreach ($organic as $position => $result) {
                    $url = $result['link'] ?? null;
                    if (! $url) {
                        $stats['skipped']++;
                        continue;
                    }

                    $sourceId = strtolower($this->tool) . ':' . substr(md5($url), 0, 12);

                    $inserted = $ingestionService->insertSignal([
                        'source' => 'serper_alternatives',
                        'source_id' => $sourceId,
                        'source_url' => $url,
                        'title' => $result['title'] ?? $query,
                        'content' => $result['snippet'] ?? '',
                        'author' => null,
                        'score' => max(0, $resultsPerQuery - $position),
                        'comment_count' => 0,
                        'category' => $this->tool,
                        'metadata' => [
                            'tool' => $this->tool,
                            'query' => $query,
                            'position' => $position + 1,
                            'domain' => parse_url($url, PHP_URL_HOST),
                        ],
                        'published_at' => now(),
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
            Log::error('Alternatives search ingestion failed', [
                'tool' => $this->tool,
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
