<?php

namespace App\Console\Commands;

use App\Models\IngestionRun;
use App\Models\RawSignal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ingestion:stats')]
#[Description('Show ingestion statistics')]
class IngestionStatsCommand extends Command
{
    public function handle(): int
    {
        $sources = RawSignal::distinct()->orderBy('source')->pluck('source');

        $this->info('=== Signal Counts ===');
        $this->table(
            ['Source', 'Total', 'Unprocessed', 'Flagged'],
            $sources->map(fn ($source) => [
                $source,
                RawSignal::where('source', $source)->count(),
                RawSignal::where('source', $source)->where('processed', false)->count(),
                RawSignal::where('source', $source)->where('flagged', true)->count(),
            ])
        );

        $this->info('=== Last Ingestion Run per Source ===');
        $lastRuns = IngestionRun::selectRaw('source, MAX(created_at) as last_run, SUM(signals_inserted) as total_inserted')
            ->groupBy('source')
            ->get();

        $this->table(
            ['Source', 'Last Run', 'Total Inserted (all time)'],
            $lastRuns->map(fn ($run) => [
                $run->source,
                $run->last_run,
                $run->total_inserted,
            ])
        );

        return self::SUCCESS;
    }
}
