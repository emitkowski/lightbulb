<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApifyService
{
    private const BASE_URL = 'https://api.apify.com/v2';

    /**
     * Run an Apify actor synchronously and return its dataset items.
     * Returns an empty array when the token is not configured or the run fails.
     *
     * @param  array<string, mixed>  $input
     * @return array<int, array<string, mixed>>
     */
    public function runSync(string $actorId, array $input): array
    {
        $token = config('ingestion.apify.token');

        if (! $token) {
            return [];
        }

        $timeoutSecs = (int) config('ingestion.apify.timeout_secs', 120);
        $memoryMbytes = (int) config('ingestion.apify.memory_mbytes', 512);

        // Apify's API requires actor IDs in "username~actor-name" form in the URL path —
        // a literal "/" (the human-readable "username/actor-name" form used everywhere
        // else, including on apify.com itself) 404s because it's parsed as extra path segments.
        $urlSafeActorId = str_replace('/', '~', $actorId);

        $response = Http::timeout($timeoutSecs + 30)
            ->withQueryParameters([
                'token' => $token,
                'timeout' => $timeoutSecs,
                'memory' => $memoryMbytes,
            ])
            ->post(self::BASE_URL . "/acts/{$urlSafeActorId}/run-sync-get-dataset-items", $input);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Apify actor {$actorId} returned HTTP {$response->status()}"
            );
        }

        return $response->json() ?? [];
    }

    public function hasToken(): bool
    {
        return (bool) config('ingestion.apify.token');
    }
}
