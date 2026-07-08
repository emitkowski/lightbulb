<?php

namespace Tests\Feature;

use App\Filament\Resources\IngestionRunResource\Pages\ListIngestionRuns;
use App\Jobs\Ingestion\IngestHackerNewsSignalsJob;
use App\Jobs\Ingestion\IngestRedditSignalsJob;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ListIngestionRunsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_run_ingestion_action_dispatches_hackernews_jobs_only(): void
    {
        Queue::fake();

        Livewire::test(ListIngestionRuns::class)
            ->callAction('run_ingestion', data: ['source' => 'hackernews'])
            ->assertHasNoActionErrors();

        Queue::assertPushed(IngestHackerNewsSignalsJob::class, count(config('ingestion.hackernews.queries')));
        Queue::assertNotPushed(IngestRedditSignalsJob::class);
    }

    public function test_run_ingestion_action_dispatches_reddit_jobs_only(): void
    {
        Queue::fake();

        Livewire::test(ListIngestionRuns::class)
            ->callAction('run_ingestion', data: ['source' => 'reddit'])
            ->assertHasNoActionErrors();

        $expectedCount = count(config('ingestion.reddit.subreddits')) * count(config('ingestion.reddit.queries'));

        Queue::assertPushed(IngestRedditSignalsJob::class, $expectedCount);
        Queue::assertNotPushed(IngestHackerNewsSignalsJob::class);
    }

    public function test_run_ingestion_action_dispatches_both_sources_for_all(): void
    {
        Queue::fake();

        Livewire::test(ListIngestionRuns::class)
            ->callAction('run_ingestion', data: ['source' => 'all'])
            ->assertHasNoActionErrors();

        Queue::assertPushed(IngestHackerNewsSignalsJob::class, count(config('ingestion.hackernews.queries')));
        Queue::assertPushed(
            IngestRedditSignalsJob::class,
            count(config('ingestion.reddit.subreddits')) * count(config('ingestion.reddit.queries'))
        );
    }
}
