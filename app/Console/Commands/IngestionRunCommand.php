<?php

namespace App\Console\Commands;

use App\Jobs\Ingestion\IngestAlternativesSearchJob;
use App\Jobs\Ingestion\IngestAppSumoSignalsJob;
use App\Jobs\Ingestion\IngestCapterraBuyerGuidesJob;
use App\Jobs\Ingestion\IngestChromeExtensionSignalsJob;
use App\Jobs\Ingestion\IngestDevToSignalsJob;
use App\Jobs\Ingestion\IngestFreelancePlatformsJob;
use App\Jobs\Ingestion\IngestGitHubIssuesJob;
use App\Jobs\Ingestion\IngestG2ReviewsJob;
use App\Jobs\Ingestion\IngestGumroadSignalsJob;
use App\Jobs\Ingestion\IngestGuruSignalsJob;
use App\Jobs\Ingestion\IngestHackerNewsSignalsJob;
use App\Jobs\Ingestion\IngestIndeedSignalsJob;
use App\Jobs\Ingestion\IngestLaraJobsSignalsJob;
use App\Jobs\Ingestion\IngestLinkedInJobsSignalsJob;
use App\Jobs\Ingestion\IngestPeoplePerHourSignalsJob;
use App\Jobs\Ingestion\IngestProductHuntSignalsJob;
use App\Jobs\Ingestion\IngestProductRoadmapsJob;
use App\Jobs\Ingestion\IngestRedditSignalsJob;
use App\Jobs\Ingestion\IngestStackOverflowSignalsJob;
use App\Jobs\Ingestion\IngestTwitterSignalsJob;
use App\Jobs\Ingestion\IngestVSCodeMarketplaceSignalsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ingestion:run {--source= : Run only a specific source (reddit, hackernews, github_issues, vscode_marketplace, stackoverflow, producthunt, devto, twitter, alternatives, roadmaps, capterra, g2, appsumo, chrome, gumroad, freelance, peopleperhour, guru, larajobs, indeed, linkedin)} {--limit= : Cap the number of queries/categories dispatched per source, for cheap smoke-testing} {--free-only : Only run sources that require no API key at all}')]
#[Description('Run signal ingestion from configured sources')]
class IngestionRunCommand extends Command
{
    private const VALID_SOURCES = [
        'reddit', 'hackernews', 'github_issues', 'vscode_marketplace',
        'stackoverflow', 'producthunt', 'devto', 'twitter',
        'alternatives', 'roadmaps', 'capterra',
        'g2', 'appsumo', 'chrome', 'gumroad', 'freelance',
        'peopleperhour', 'guru', 'larajobs', 'indeed', 'linkedin',
    ];

    /**
     * Sources that need zero API key / credential of any kind, confirmed by
     * live testing. Every other source requires at least one env var to be set.
     */
    private const FREE_SOURCES = [
        'hackernews', 'github_issues', 'vscode_marketplace',
        'stackoverflow', 'devto', 'larajobs',
    ];

    public function handle(): int
    {
        $source = $this->option('source');
        $freeOnly = (bool) $this->option('free-only');

        if ($source && ! in_array($source, self::VALID_SOURCES)) {
            $this->error("Unknown source: {$source}. Valid options: " . implode(', ', self::VALID_SOURCES));
            return self::FAILURE;
        }

        if ($source && $freeOnly && ! in_array($source, self::FREE_SOURCES)) {
            $this->error("--free-only was set but '{$source}' requires an API key. Free sources: " . implode(', ', self::FREE_SOURCES));
            return self::FAILURE;
        }

        if ($freeOnly) {
            $this->info('Running in --free-only mode — skipping every source that needs an API key.');
        }

        if ($this->shouldRun('reddit', $source, $freeOnly)) {
            $this->runReddit();
        }

        if ($this->shouldRun('hackernews', $source, $freeOnly)) {
            $this->runHackerNews();
        }

        if ($this->shouldRun('github_issues', $source, $freeOnly)) {
            $this->runGitHubIssues();
        }

        if ($this->shouldRun('vscode_marketplace', $source, $freeOnly)) {
            $this->runVSCodeMarketplace();
        }

        if ($this->shouldRun('stackoverflow', $source, $freeOnly)) {
            $this->runStackOverflow();
        }

        if ($this->shouldRun('producthunt', $source, $freeOnly)) {
            $this->runProductHunt();
        }

        if ($this->shouldRun('devto', $source, $freeOnly)) {
            $this->runDevTo();
        }

        if ($this->shouldRun('twitter', $source, $freeOnly)) {
            $this->runTwitter();
        }

        if ($this->shouldRun('alternatives', $source, $freeOnly)) {
            $this->runAlternatives();
        }

        if ($this->shouldRun('roadmaps', $source, $freeOnly)) {
            $this->runRoadmaps();
        }

        if ($this->shouldRun('capterra', $source, $freeOnly)) {
            $this->runCapterra();
        }

        if ($this->shouldRun('g2', $source, $freeOnly)) {
            $this->runG2();
        }

        if ($this->shouldRun('appsumo', $source, $freeOnly)) {
            $this->runAppSumo();
        }

        if ($this->shouldRun('chrome', $source, $freeOnly)) {
            $this->runChrome();
        }

        if ($this->shouldRun('gumroad', $source, $freeOnly)) {
            $this->runGumroad();
        }

        if ($this->shouldRun('freelance', $source, $freeOnly)) {
            $this->runFreelance();
        }

        if ($this->shouldRun('peopleperhour', $source, $freeOnly)) {
            $this->runPeoplePerHour();
        }

        if ($this->shouldRun('guru', $source, $freeOnly)) {
            $this->runGuru();
        }

        if ($this->shouldRun('larajobs', $source, $freeOnly)) {
            $this->runLaraJobs();
        }

        if ($this->shouldRun('indeed', $source, $freeOnly)) {
            $this->runIndeed();
        }

        if ($this->shouldRun('linkedin', $source, $freeOnly)) {
            $this->runLinkedIn();
        }

        return self::SUCCESS;
    }

    /**
     * Decide whether a given source should run this invocation: it matches an
     * explicit --source filter (or none was given), and isn't excluded by --free-only.
     */
    private function shouldRun(string $candidate, ?string $requestedSource, bool $freeOnly): bool
    {
        if ($requestedSource && $requestedSource !== $candidate) {
            return false;
        }

        if ($freeOnly && ! in_array($candidate, self::FREE_SOURCES)) {
            return false;
        }

        return true;
    }

    /**
     * Apply the --limit option to a config-driven list of queries/categories/topics,
     * so a run can be capped cheaply (e.g. smoke-testing a keyed source without
     * burning through its full query list / API quota).
     *
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function applyLimit(array $items): array
    {
        $limit = $this->option('limit');

        if ($limit === null) {
            return $items;
        }

        return array_slice($items, 0, max(0, (int) $limit));
    }

    private function runReddit(): void
    {
        $subreddits = $this->applyLimit(config('ingestion.reddit.subreddits', []));
        $queries = $this->applyLimit(config('ingestion.reddit.queries', []));
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
        $queries = $this->applyLimit(config('ingestion.hackernews.queries', []));
        $this->info('Dispatching ' . count($queries) . ' HackerNews ingestion jobs...');

        foreach ($queries as $query) {
            IngestHackerNewsSignalsJob::dispatchSync($query);
        }

        $this->info('HackerNews ingestion complete.');
    }

    private function runGitHubIssues(): void
    {
        $repos = $this->applyLimit(config('ingestion.github.repositories', []));
        $this->info('Dispatching ' . count($repos) . ' GitHub Issues ingestion jobs...');

        foreach ($repos as $repo) {
            IngestGitHubIssuesJob::dispatchSync($repo);
        }

        $this->info('GitHub Issues ingestion complete.');
    }

    private function runVSCodeMarketplace(): void
    {
        $queries = $this->applyLimit(config('ingestion.vscode.search_queries', []));
        $this->info('Dispatching ' . count($queries) . ' VS Code Marketplace ingestion jobs...');

        foreach ($queries as $query) {
            IngestVSCodeMarketplaceSignalsJob::dispatchSync($query);
        }

        $this->info('VS Code Marketplace ingestion complete.');
    }

    private function runStackOverflow(): void
    {
        $tags = $this->applyLimit(config('ingestion.stackoverflow.tags', []));
        $this->info('Dispatching ' . count($tags) . ' Stack Overflow ingestion jobs...');

        foreach ($tags as $tag) {
            IngestStackOverflowSignalsJob::dispatchSync($tag);
        }

        $this->info('Stack Overflow ingestion complete.');
    }

    private function runProductHunt(): void
    {
        $topics = $this->applyLimit(config('ingestion.producthunt.topics', []));
        $this->info('Dispatching ' . count($topics) . ' Product Hunt ingestion jobs...');

        foreach ($topics as $topic) {
            IngestProductHuntSignalsJob::dispatchSync($topic);
        }

        $this->info('Product Hunt ingestion complete.');
    }

    private function runDevTo(): void
    {
        $tags = $this->applyLimit(config('ingestion.devto.tags', []));
        $this->info('Dispatching ' . count($tags) . ' Dev.to ingestion jobs...');

        foreach ($tags as $tag) {
            IngestDevToSignalsJob::dispatchSync($tag);
        }

        $this->info('Dev.to ingestion complete.');
    }

    private function runTwitter(): void
    {
        $queries = $this->applyLimit(config('ingestion.twitter.queries', []));
        $this->info('Dispatching ' . count($queries) . ' Twitter ingestion jobs...');

        foreach ($queries as $query) {
            IngestTwitterSignalsJob::dispatchSync($query);
        }

        $this->info('Twitter ingestion complete.');
    }

    private function runAlternatives(): void
    {
        $tools = $this->applyLimit(config('ingestion.serper.alternatives.tools', []));
        $this->info('Dispatching ' . count($tools) . ' Serper alternatives ingestion jobs...');

        foreach ($tools as $tool) {
            IngestAlternativesSearchJob::dispatchSync($tool);
        }

        $this->info('Serper alternatives ingestion complete.');
    }

    private function runRoadmaps(): void
    {
        $tools = $this->applyLimit(config('ingestion.serper.roadmaps.tools', []));
        $this->info('Dispatching ' . count($tools) . ' product roadmap ingestion jobs...');

        foreach ($tools as $tool) {
            IngestProductRoadmapsJob::dispatchSync($tool);
        }

        $this->info('Product roadmaps ingestion complete.');
    }

    private function runCapterra(): void
    {
        $categories = $this->applyLimit(config('ingestion.serper.capterra.categories', []));
        $this->info('Dispatching ' . count($categories) . ' Capterra buyer guide ingestion jobs...');

        foreach ($categories as $category) {
            IngestCapterraBuyerGuidesJob::dispatchSync($category);
        }

        $this->info('Capterra buyer guides ingestion complete.');
    }

    private function runG2(): void
    {
        $categories = $this->applyLimit(config('ingestion.apify.g2.categories', []));
        $this->info('Dispatching ' . count($categories) . ' G2 reviews ingestion jobs...');

        foreach ($categories as $category) {
            IngestG2ReviewsJob::dispatchSync($category);
        }

        $this->info('G2 reviews ingestion complete.');
    }

    private function runAppSumo(): void
    {
        $categories = $this->applyLimit(config('ingestion.apify.appsumo.categories', []));
        $this->info('Dispatching ' . count($categories) . ' AppSumo ingestion jobs...');

        foreach ($categories as $category) {
            IngestAppSumoSignalsJob::dispatchSync($category);
        }

        $this->info('AppSumo ingestion complete.');
    }

    private function runChrome(): void
    {
        $categories = $this->applyLimit(config('ingestion.apify.chrome.categories', []));
        $this->info('Dispatching ' . count($categories) . ' Chrome Web Store ingestion jobs...');

        foreach ($categories as $category) {
            IngestChromeExtensionSignalsJob::dispatchSync($category);
        }

        $this->info('Chrome Web Store ingestion complete.');
    }

    private function runGumroad(): void
    {
        $searchTerms = $this->applyLimit(config('ingestion.apify.gumroad.search_terms', []));
        $this->info('Dispatching ' . count($searchTerms) . ' Gumroad ingestion jobs...');

        foreach ($searchTerms as $term) {
            IngestGumroadSignalsJob::dispatchSync($term);
        }

        $this->info('Gumroad ingestion complete.');
    }

    private function runFreelance(): void
    {
        $queries = $this->applyLimit(config('ingestion.apify.freelance.queries', []));
        $this->info('Dispatching ' . count($queries) . ' freelance platforms ingestion jobs...');

        foreach ($queries as $query) {
            IngestFreelancePlatformsJob::dispatchSync($query);
        }

        $this->info('Freelance platforms ingestion complete.');
    }

    private function runPeoplePerHour(): void
    {
        $queries = $this->applyLimit(config('ingestion.apify.freelance.queries', []));
        $this->info('Dispatching ' . count($queries) . ' PeoplePerHour ingestion jobs...');

        foreach ($queries as $query) {
            IngestPeoplePerHourSignalsJob::dispatchSync($query);
        }

        $this->info('PeoplePerHour ingestion complete.');
    }

    private function runGuru(): void
    {
        $queries = $this->applyLimit(config('ingestion.apify.freelance.queries', []));
        $this->info('Dispatching ' . count($queries) . ' Guru ingestion jobs...');

        foreach ($queries as $query) {
            IngestGuruSignalsJob::dispatchSync($query);
        }

        $this->info('Guru ingestion complete.');
    }

    private function runLaraJobs(): void
    {
        $this->info('Dispatching LaraJobs ingestion job...');
        IngestLaraJobsSignalsJob::dispatchSync();
        $this->info('LaraJobs ingestion complete.');
    }

    private function runIndeed(): void
    {
        $queries = $this->applyLimit(config('ingestion.job_boards.queries', []));
        $this->info('Dispatching ' . count($queries) . ' Indeed ingestion jobs...');

        foreach ($queries as $query) {
            IngestIndeedSignalsJob::dispatchSync($query);
        }

        $this->info('Indeed ingestion complete.');
    }

    private function runLinkedIn(): void
    {
        $queries = $this->applyLimit(config('ingestion.job_boards.queries', []));
        $this->info('Dispatching ' . count($queries) . ' LinkedIn ingestion jobs...');

        foreach ($queries as $query) {
            IngestLinkedInJobsSignalsJob::dispatchSync($query);
        }

        $this->info('LinkedIn ingestion complete.');
    }
}
