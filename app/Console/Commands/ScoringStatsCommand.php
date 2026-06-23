<?php

namespace App\Console\Commands;

use App\Models\Idea;
use App\Models\RawSignal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scoring:stats')]
#[Description('Show scoring pipeline statistics')]
class ScoringStatsCommand extends Command
{
    public function handle(): int
    {
        $this->info('Scoring pipeline stats');
        $this->newLine();

        // Raw signals
        $totalSignals = RawSignal::count();
        $unprocessed = RawSignal::where('processed', false)->count();
        $this->table(['Signals', 'Count'], [
            ['Total', $totalSignals],
            ['Unprocessed', $unprocessed],
            ['Processed', $totalSignals - $unprocessed],
        ]);

        $this->newLine();

        // Ideas by status
        $byStatus = Idea::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusRows = array_map(
            fn ($status, $count) => [$status, $count],
            array_keys($byStatus),
            array_values($byStatus)
        );

        $this->table(['Ideas by Status', 'Count'], $statusRows ?: [['—', 0]]);

        $this->newLine();

        // Top scored ideas
        $top = Idea::where('status', 'scored')
            ->orderByDesc('score_overall')
            ->limit(10)
            ->get(['title', 'score_overall']);

        if ($top->isNotEmpty()) {
            $this->info('Top scored ideas:');
            $this->table(
                ['Title', 'Score'],
                $top->map(fn ($i) => [str($i->title)->limit(60), $i->score_overall])->toArray()
            );
        }

        return self::SUCCESS;
    }
}
