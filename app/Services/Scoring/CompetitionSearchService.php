<?php

namespace App\Services\Scoring;

use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompetitionSearchService
{
    /**
     * Search for competitors using Serper.dev.
     *
     * Returns an array with 'results' (organic hits) and 'summary' (text for the scoring agent).
     * When SERPER_API_KEY is not set, returns a stub result so the pipeline can run.
     *
     * @return array{results: array<int, array<string, mixed>>, summary: string, stubbed: bool}
     */
    public function search(string $query): array
    {
        $apiKey = config('scoring.serper.api_key');

        if (! $apiKey) {
            return $this->stubbedResult($query);
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(config('scoring.serper.timeout', 15))
                ->post(config('scoring.serper.base_url') . '/search', [
                    'q' => $query . ' tool software alternative',
                    'num' => config('scoring.serper.results_count', 10),
                ]);

            if (! $response->successful()) {
                Log::warning('Serper search failed', ['status' => $response->status(), 'query' => $query]);

                return $this->stubbedResult($query);
            }

            $organic = $response->json('organic', []);

            return [
                'results' => $organic,
                'summary' => $this->formatSummary($organic),
                'stubbed' => false,
            ];
        } catch (Throwable $e) {
            Log::warning('Serper search exception', ['query' => $query, 'error' => $e->getMessage()]);

            return $this->stubbedResult($query);
        }
    }

    /** @return array{results: array<int, array<string, mixed>>, summary: string, stubbed: bool} */
    private function stubbedResult(string $query): array
    {
        return [
            'results' => [],
            'summary' => "[STUB] Competition search not available — SERPER_API_KEY not set. Query was: \"{$query}\". Assume moderate competition until key is configured.",
            'stubbed' => true,
        ];
    }

    /** @param array<int, array<string, mixed>> $organic */
    private function formatSummary(array $organic): string
    {
        if (empty($organic)) {
            return 'No significant competitors found in search results.';
        }

        $lines = array_map(
            fn ($r) => '- ' . ($r['title'] ?? '') . ': ' . ($r['snippet'] ?? ''),
            array_slice($organic, 0, 5)
        );

        return implode("\n", $lines);
    }
}
