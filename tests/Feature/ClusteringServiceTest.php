<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\RawSignal;
use App\Services\Scoring\ClusteringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClusteringServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClusteringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClusteringService();
    }

    public function test_cluster_groups_related_signals_into_ideas(): void
    {
        $signals = RawSignal::factory()->count(3)->create([
            'title' => 'I wish there was a tool for webhook delivery',
            'content' => 'We have been sending webhooks manually, need automation',
            'score' => 50,
            'processed' => false,
        ]);

        $ideas = $this->service->cluster($signals);

        $this->assertGreaterThanOrEqual(1, $ideas->count());
        $this->assertDatabaseCount('ideas', $ideas->count());
    }

    public function test_cluster_marks_signals_as_processed(): void
    {
        $signals = RawSignal::factory()->count(2)->create([
            'title' => 'webhook delivery automation tool',
            'content' => 'automated webhook retry delivery system',
            'processed' => false,
        ]);

        $this->service->cluster($signals);

        foreach ($signals as $signal) {
            $this->assertDatabaseHas('raw_signals', ['id' => $signal->id, 'processed' => true]);
        }
    }

    public function test_cluster_creates_idea_signals_pivot_records(): void
    {
        $signals = RawSignal::factory()->count(2)->create([
            'title' => 'automated webhook retry tool needed',
            'content' => 'webhook delivery retry automation system',
            'processed' => false,
        ]);

        $this->service->cluster($signals);

        $idea = Idea::first();
        $this->assertNotNull($idea);
        $this->assertSame(2, $idea->signals()->count());
    }

    public function test_cluster_skips_singletons_below_minimum_signal_count(): void
    {
        // Two signals with no keyword overlap → two singleton clusters, both below min=2
        $signals = collect([
            RawSignal::factory()->create([
                'title' => 'xyzabc random unique thing',
                'content' => 'pqrstu something unrelated here',
                'processed' => false,
            ]),
            RawSignal::factory()->create([
                'title' => 'completely different topic world',
                'content' => 'never overlap keywords anywhere else',
                'processed' => false,
            ]),
        ]);

        $ideas = $this->service->cluster($signals);

        $this->assertSame(0, $ideas->count());
    }

    public function test_cluster_returns_empty_collection_for_empty_input(): void
    {
        $ideas = $this->service->cluster(collect());

        $this->assertSame(0, $ideas->count());
    }

    public function test_cluster_sets_source_signals_count_on_idea(): void
    {
        $signals = RawSignal::factory()->count(3)->create([
            'title' => 'webhook delivery retry automation',
            'content' => 'automated webhook delivery retry system',
            'processed' => false,
        ]);

        $this->service->cluster($signals);

        $idea = Idea::first();
        $this->assertNotNull($idea);
        $this->assertGreaterThanOrEqual(2, $idea->source_signals_count);
    }
}
