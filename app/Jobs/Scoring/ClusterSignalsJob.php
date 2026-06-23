<?php

namespace App\Jobs\Scoring;

use App\Models\RawSignal;
use App\Services\Scoring\ClusteringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClusterSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        protected int $limit = 50
    ) {}

    public function handle(ClusteringService $clustering): void
    {
        $signals = RawSignal::where('processed', false)
            ->orderBy('score', 'desc')
            ->limit($this->limit)
            ->get();

        if ($signals->isEmpty()) {
            Log::info('ClusterSignalsJob: no unprocessed signals found');

            return;
        }

        $ideas = $clustering->cluster($signals);

        Log::info('ClusterSignalsJob complete', [
            'signals_processed' => $signals->count(),
            'ideas_created' => $ideas->count(),
        ]);
    }
}
