<?php

namespace Tests\Feature;

use App\Models\IngestionRun;
use App\Models\RawSignal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestionStatsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_every_distinct_source_present_in_raw_signals(): void
    {
        RawSignal::factory()->create(['source' => 'twitter']);
        RawSignal::factory()->create(['source' => 'reddit']);
        RawSignal::factory()->create(['source' => 'g2']);

        $this->artisan('ingestion:stats')
            ->expectsOutputToContain('twitter')
            ->expectsOutputToContain('reddit')
            ->expectsOutputToContain('g2')
            ->assertExitCode(0);
    }

    public function test_shows_last_run_per_source(): void
    {
        IngestionRun::factory()->create(['source' => 'twitter', 'signals_inserted' => 5]);

        $this->artisan('ingestion:stats')
            ->expectsOutputToContain('twitter')
            ->assertExitCode(0);
    }

    public function test_runs_cleanly_with_no_signals(): void
    {
        $this->artisan('ingestion:stats')->assertExitCode(0);
    }
}
