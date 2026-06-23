<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\RawSignal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringRunCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_command_clusters_unprocessed_signals(): void
    {
        RawSignal::factory()->count(3)->create([
            'title' => 'automated webhook delivery retry tool',
            'content' => 'webhook delivery retry automation system',
            'processed' => false,
        ]);

        $this->artisan('scoring:run --cluster')->assertSuccessful();

        $this->assertDatabaseHas('ideas', ['status' => 'pending']);
    }

    public function test_run_command_scores_pending_ideas(): void
    {
        Idea::factory()->count(2)->create(['status' => 'pending']);

        $this->artisan('scoring:run --score')->assertSuccessful();

        $this->assertDatabaseHas('ideas', ['status' => 'scored']);
    }

    public function test_run_command_exits_cleanly_with_no_signals(): void
    {
        $this->artisan('scoring:run')->assertSuccessful();
    }

    public function test_stats_command_outputs_signal_and_idea_counts(): void
    {
        RawSignal::factory()->count(5)->create(['processed' => false]);
        Idea::factory()->scored()->count(2)->create();

        $this->artisan('scoring:stats')
            ->assertSuccessful()
            ->expectsOutputToContain('5');
    }
}
