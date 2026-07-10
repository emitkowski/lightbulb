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

/**
 * Stripe's own /customers and /partners/directory pages are client-rendered (no
 * server HTML to scrape directly, confirmed live 2026-07-10 — zero case-study
 * links found in a plain GET, unlike Paddle's server-rendered equivalent). Reuses
 * the Serper site-search pattern from Layer 5 (Indie Hackers) instead.
 */
class IngestStripeCustomersSearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Index pages Serper occasionally surfaces that aren't individual case studies. */
    private const SKIP_URLS = ['https://stripe.com/customers', 'https://stripe.com/customers/all'];

    public function __construct(protected string $category) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);

        $run = $ingestionService->startRun('stripe_customers', $this->category);

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

            $resultsPerQuery = config('ingestion.serper.stripe_customers.results_per_query', 10);
            $searchQuery = "site:stripe.com/customers {$this->category}";

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
                if (! $url || in_array(rtrim($url, '/'), self::SKIP_URLS, true)) {
                    $skipped++;
                    continue;
                }

                // Not query-prefixed (same reasoning as Layer 5's Indie Hackers job):
                // the same case study surfacing under two category searches should
                // dedupe to one signal rather than being stored twice.
                $sourceId = substr(md5($url), 0, 16);

                $wasInserted = $ingestionService->insertSignal([
                    'source' => 'stripe_customers',
                    'source_id' => $sourceId,
                    'source_url' => $url,
                    'title' => $result['title'] ?? $this->category,
                    'content' => $result['snippet'] ?? '',
                    'author' => null,
                    'score' => max(0, $resultsPerQuery - $position),
                    'comment_count' => 0,
                    'category' => $this->category,
                    'metadata' => [
                        'search_query' => $searchQuery,
                        'position' => $position + 1,
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
            Log::error('Stripe customers ingestion failed', [
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
