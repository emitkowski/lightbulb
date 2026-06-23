<?php

namespace App\Console\Commands;

use App\Jobs\Scoring\ClusterSignalsJob;
use App\Jobs\Scoring\ScoreIdeaJob;
use App\Models\Idea;
use App\Models\RawSignal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scoring:run {--cluster : Only run clustering, skip scoring} {--score : Only run scoring, skip clustering} {--limit=10 : Max ideas to score per run}')]
#[Description('Run the scoring pipeline: cluster raw signals into ideas, then score each idea')]
class ScoringRunCommand extends Command
{
    public function handle(): int
    {
        $clusterOnly = $this->option('cluster');
        $scoreOnly = $this->option('score');
        $limit = (int) $this->option('limit');

        if (! $scoreOnly) {
            $this->runClustering();
        }

        if (! $clusterOnly) {
            $this->runScoring($limit);
        }

        return self::SUCCESS;
    }

    private function runClustering(): void
    {
        $unprocessed = RawSignal::where('processed', false)->count();
        $this->info("Clustering {$unprocessed} unprocessed signal(s)...");

        $batchSize = config('scoring.pipeline.cluster_batch_size', 50);
        (new ClusterSignalsJob($batchSize))->handle(app(\App\Services\Scoring\ClusteringService::class));

        $pending = Idea::where('status', 'pending')->count();
        $this->info("Clustering complete. {$pending} idea(s) pending scoring.");
    }

    private function runScoring(int $limit): void
    {
        $ideas = Idea::where('status', 'pending')->limit($limit)->get();

        if ($ideas->isEmpty()) {
            $this->info('No pending ideas to score.');

            return;
        }

        $this->info("Scoring {$ideas->count()} idea(s)...");
        $bar = $this->output->createProgressBar($ideas->count());
        $bar->start();

        foreach ($ideas as $idea) {
            (new ScoreIdeaJob($idea))->handle(
                app(\App\Services\Scoring\ScoringAgentService::class),
                app(\App\Services\Scoring\CompetitionSearchService::class)
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Scoring complete.');
    }
}
