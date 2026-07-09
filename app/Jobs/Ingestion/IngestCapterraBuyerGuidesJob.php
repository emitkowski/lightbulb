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

class IngestCapterraBuyerGuidesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $category) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('serper_capterra', $this->category);

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

            $resultsPerCategory = config('ingestion.serper.capterra.results_per_category', 5);
            $query = "site:capterra.com {$this->category} buyers guide";

            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post(config('scoring.serper.base_url') . '/search', [
                    'q' => $query,
                    'num' => $resultsPerCategory,
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

                $sourceId = strtolower(str_replace(' ', '_', $this->category)) . ':' . substr(md5($url), 0, 12);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'serper_capterra',
                    'source_id' => $sourceId,
                    'source_url' => $url,
                    'title' => $result['title'] ?? "Capterra {$this->category} guide",
                    'content' => $result['snippet'] ?? '',
                    'author' => null,
                    'score' => max(0, $resultsPerCategory - $position),
                    'comment_count' => 0,
                    'category' => $this->category,
                    'metadata' => [
                        'category' => $this->category,
                        'query' => $query,
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
            Log::error('Capterra buyer guides ingestion failed', [
                'category' => $this->category,
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
