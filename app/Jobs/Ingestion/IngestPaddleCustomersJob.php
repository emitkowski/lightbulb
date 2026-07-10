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
 * Ingests Paddle's public customer case-study directory (paddle.com/customers).
 * Unlike Stripe's own customer/partner pages (client-rendered, no server HTML),
 * Paddle's page is server-rendered and directly scrapable — no Apify actor needed.
 * See Layer 11 in docs/build/signal-sources.md for why Lemon Squeezy's Discover
 * page isn't a source here (discontinued post-Stripe-acquisition, 404s as of 2026-07).
 */
class IngestPaddleCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Index links that aren't individual case studies. */
    private const SKIP_HREFS = ['/customers', '/customers/', '/customers/all'];

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $url = config('ingestion.paddle_customers.url', 'https://www.paddle.com/customers');
        $run = $ingestionService->startRun('paddle_customers', $url);

        try {
            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "Paddle customers page error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $caseStudies = $this->extractCaseStudies($response->body());
            $stats['found'] = count($caseStudies);

            foreach ($caseStudies as $href => $text) {
                $slug = trim(str_replace('/customers/', '', $href), '/');

                $inserted = $ingestionService->insertSignal([
                    'source' => 'paddle_customers',
                    'source_id' => $slug,
                    'source_url' => 'https://www.paddle.com' . $href,
                    'title' => substr($text, 0, 250),
                    'content' => $text,
                    'author' => null,
                    'score' => 0,
                    'comment_count' => 0,
                    'category' => 'customer_story',
                    'metadata' => ['slug' => $slug],
                    'published_at' => now(),
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
            Log::error('Paddle customers ingestion failed', ['error' => $e->getMessage()]);
            $ingestionService->finishRun($run, [
                'found' => 0, 'inserted' => 0, 'skipped' => 0,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
        }
    }

    /**
     * Paddle lists each case study twice on the page (a compact nav-style link and
     * a fuller card) — same href, different text. Keep the longer text per href,
     * since it's consistently the complete descriptive sentence, not a fragment.
     *
     * @return array<string, string> href => text
     */
    private function extractCaseStudies(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        // Malformed HTML5 tags otherwise emit E_WARNING, which PHPUnit converts to
        // an exception (see docs/memory/testing.md, 2026-06-26) — suppress via flags.
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//a[starts-with(@href, "/customers/")]');

        $best = [];
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (in_array($href, self::SKIP_HREFS, true)) {
                continue;
            }

            $text = trim(preg_replace('/\s+/', ' ', $link->textContent));
            if ($text === '') {
                continue;
            }

            if (! isset($best[$href]) || strlen($text) > strlen($best[$href])) {
                $best[$href] = $text;
            }
        }

        return $best;
    }
}
