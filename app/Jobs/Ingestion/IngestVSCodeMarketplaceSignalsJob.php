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

class IngestVSCodeMarketplaceSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const EXTENSION_QUERY_URL = 'https://marketplace.visualstudio.com/_apis/public/gallery/extensionquery';

    // filterType 8 = Target, filterType 10 = SearchText
    // sortBy 4 = InstallCount desc, flags 0x180 = IncludeStatistics + IncludeCategoryAndTags
    private const FLAGS = 384;

    public function __construct(protected string $searchQuery) {}

    public function handle(IngestionService $ingestionService): void
    {
        $startedAt = microtime(true);
        $stats = ['found' => 0, 'inserted' => 0, 'skipped' => 0, 'status' => 'success'];

        $run = $ingestionService->startRun('vscode_marketplace', $this->searchQuery);

        try {
            $minInstalls = config('ingestion.vscode.min_install_count', 50000);
            $maxRating = config('ingestion.vscode.max_weighted_rating', 3.9);
            $minRatingCount = config('ingestion.vscode.min_rating_count', 50);
            $maxPerQuery = config('ingestion.vscode.max_per_query', 25);

            $response = Http::withHeaders([
                'Accept' => 'application/json;api-version=3.0-preview.1',
                'Content-Type' => 'application/json',
            ])
                ->timeout(20)
                ->post(self::EXTENSION_QUERY_URL, [
                    'filters' => [[
                        'criteria' => [
                            ['filterType' => 8, 'value' => 'Microsoft.VisualStudio.Code'],
                            ['filterType' => 10, 'value' => $this->searchQuery],
                        ],
                        'pageNumber' => 1,
                        'pageSize' => $maxPerQuery,
                        'sortBy' => 4,
                        'sortOrder' => 0,
                    ]],
                    'flags' => self::FLAGS,
                ]);

            if (! $response->successful()) {
                $ingestionService->finishRun($run, [
                    'found' => 0, 'inserted' => 0, 'skipped' => 0,
                    'status' => 'failed',
                    'error' => "VS Code Marketplace API error: {$response->status()}",
                    'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                ]);
                return;
            }

            $extensions = $response->json('results.0.extensions', []);
            $stats['found'] = count($extensions);

            foreach ($extensions as $ext) {
                $publisher = $ext['publisher']['publisherName'] ?? null;
                $extensionName = $ext['extensionName'] ?? null;

                if (! $publisher || ! $extensionName) {
                    $stats['skipped']++;
                    continue;
                }

                $extStats = $this->indexStats($ext['statistics'] ?? []);
                $installCount = (int) ($extStats['install'] ?? 0);
                $weightedRating = (float) ($extStats['weightedRating'] ?? 0);
                $ratingCount = (int) ($extStats['ratingcount'] ?? 0);

                if ($installCount < $minInstalls || $ratingCount < $minRatingCount || $weightedRating > $maxRating) {
                    $stats['skipped']++;
                    continue;
                }

                $inserted = $ingestionService->insertSignal([
                    'source' => 'vscode_marketplace',
                    'source_id' => "{$publisher}.{$extensionName}",
                    'source_url' => "https://marketplace.visualstudio.com/items?itemName={$publisher}.{$extensionName}",
                    'title' => $ext['displayName'] ?? "{$publisher}.{$extensionName}",
                    'content' => $ext['shortDescription'] ?? ($ext['displayName'] ?? ''),
                    'author' => $ext['publisher']['displayName'] ?? $publisher,
                    'score' => $installCount,
                    'comment_count' => $ratingCount,
                    'category' => $this->searchQuery,
                    'metadata' => [
                        'publisher' => $publisher,
                        'extension_name' => $extensionName,
                        'install_count' => $installCount,
                        'weighted_rating' => $weightedRating,
                        'rating_count' => $ratingCount,
                        'categories' => $ext['categories'] ?? [],
                        'tags' => $ext['tags'] ?? [],
                        'search_query' => $this->searchQuery,
                    ],
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

        } catch (\Throwable $e) {
            Log::error('VS Code Marketplace ingestion failed', [
                'query' => $this->searchQuery,
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

    /** @param array<int, array{statisticName: string, value: float|int}> $rawStats */
    private function indexStats(array $rawStats): array
    {
        $indexed = [];
        foreach ($rawStats as $stat) {
            $indexed[$stat['statisticName']] = $stat['value'];
        }
        return $indexed;
    }
}
