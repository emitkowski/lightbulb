<?php

namespace App\Services\Ingestion;

use App\Models\IngestionRun;
use App\Models\RawSignal;

class IngestionService
{
    public function startRun(string $source, string $query): IngestionRun
    {
        return IngestionRun::create([
            'source' => $source,
            'query' => $query,
            'signals_found' => 0,
            'signals_inserted' => 0,
            'signals_skipped' => 0,
            'status' => 'running',
        ]);
    }

    public function finishRun(IngestionRun $run, array $stats): void
    {
        $run->update([
            'signals_found' => $stats['found'] ?? 0,
            'signals_inserted' => $stats['inserted'] ?? 0,
            'signals_skipped' => $stats['skipped'] ?? 0,
            'status' => $stats['status'],
            'error_message' => $stats['error'] ?? null,
            'duration_ms' => $stats['duration_ms'] ?? null,
        ]);
    }

    public function insertSignal(array $data, ?string $ingestionRunId = null): bool
    {
        if ($this->exists($data['source'], $data['source_id'] ?? null)) {
            return false;
        }

        RawSignal::create(array_merge($data, ['ingestion_run_id' => $ingestionRunId]));

        return true;
    }

    public function exists(string $source, ?string $sourceId): bool
    {
        if ($sourceId === null) {
            return false;
        }

        return RawSignal::where('source', $source)
            ->where('source_id', $sourceId)
            ->exists();
    }
}
