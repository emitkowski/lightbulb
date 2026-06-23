<?php

namespace App\Console\Commands;

use App\Jobs\Ingestion\IngestHackerNewsSignalsJob;
use App\Jobs\Ingestion\IngestRedditSignalsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ingestion:run {--source= : Run only a specific source (reddit or hackernews)}')]
#[Description('Run signal ingestion from configured sources')]
class IngestionRunCommand extends Command
{
    public function handle(): int
    {
        $source = $this->option('source');

        if ($source && ! in_array($source, ['reddit', 'hackernews'])) {
            $this->error("Unknown source: {$source}. Valid options: reddit, hackernews");
            return self::FAILURE;
        }

        if (! $source || $source === 'reddit') {
            $this->runReddit();
        }

        if (! $source || $source === 'hackernews') {
            $this->runHackerNews();
        }

        return self::SUCCESS;
    }

    private function runReddit(): void
    {
        $subreddits = config('ingestion.reddit.subreddits', []);
        $queries = config('ingestion.reddit.queries', []);
        $total = count($subreddits) * count($queries);

        $this->info("Dispatching {$total} Reddit ingestion jobs ({$total} subreddit/query combinations)...");

        foreach ($subreddits as $subreddit) {
            foreach ($queries as $query) {
                IngestRedditSignalsJob::dispatchSync($subreddit, $query);
            }
        }

        $this->info('Reddit ingestion complete.');
    }

    private function runHackerNews(): void
    {
        $queries = config('ingestion.hackernews.queries', []);
        $this->info('Dispatching ' . count($queries) . ' HackerNews ingestion jobs...');

        foreach ($queries as $query) {
            IngestHackerNewsSignalsJob::dispatchSync($query);
        }

        $this->info('HackerNews ingestion complete.');
    }
}
