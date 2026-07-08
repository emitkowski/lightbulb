<?php

namespace Tests\Feature;

use App\Jobs\Scoring\ScoreIdeaJob;
use App\Models\Idea;
use App\Services\Scoring\ClaudeCliRunner;
use App\Services\Scoring\CompetitionSearchService;
use App\Services\Scoring\ScoringAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreIdeaJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(Idea $idea): void
    {
        (new ScoreIdeaJob($idea))->handle(
            new ScoringAgentService(new ClaudeCliRunner()),
            new CompetitionSearchService()
        );
    }

    public function test_scores_a_pending_idea_and_sets_status_to_scored(): void
    {
        $idea = Idea::factory()->create(['status' => 'pending']);

        $this->runJob($idea);

        $this->assertDatabaseHas('ideas', [
            'id' => $idea->id,
            'status' => 'scored',
        ]);
    }

    public function test_scored_idea_has_overall_score_set(): void
    {
        $idea = Idea::factory()->create(['status' => 'pending']);

        $this->runJob($idea);

        $idea->refresh();
        $this->assertNotNull($idea->score_overall);
        $this->assertGreaterThanOrEqual(0, $idea->score_overall);
        $this->assertLessThanOrEqual(100, $idea->score_overall);
    }

    public function test_scored_idea_has_specificity_gate_answered(): void
    {
        $idea = Idea::factory()->create(['status' => 'pending']);

        $this->runJob($idea);

        $idea->refresh();
        $this->assertSame('passed', $idea->specificity_gate_status);
        $this->assertNotNull($idea->specificity_gate_answers);
    }

    public function test_scored_idea_has_competition_summary(): void
    {
        $idea = Idea::factory()->create(['status' => 'pending']);

        $this->runJob($idea);

        $idea->refresh();
        $this->assertNotNull($idea->competition_summary);
    }

    public function test_scored_idea_has_processed_at_timestamp(): void
    {
        $idea = Idea::factory()->create(['status' => 'pending']);

        $this->runJob($idea);

        $idea->refresh();
        $this->assertNotNull($idea->processed_at);
    }

    public function test_skips_ideas_that_are_not_pending(): void
    {
        $idea = Idea::factory()->scored()->create();

        $this->runJob($idea);

        // Status should not have changed
        $idea->refresh();
        $this->assertSame('scored', $idea->status);
    }

    public function test_idea_is_discarded_when_kill_condition_fires(): void
    {
        // Stub the agent to return a kill condition
        $agentStub = new class(new ClaudeCliRunner()) extends ScoringAgentService {
            public function runSpecificityGate(Idea $idea): array
            {
                return [
                    'passed' => true,
                    'answers' => ['day_one_action' => 'test', 'free_tool_comparison' => 'test', 'first_paying_customer' => 'test', 'competitor_switch_reason' => 'test'],
                    'reasoning' => 'Gate passed.',
                ];
            }

            public function scoreIdea(Idea $idea, array $competitionData): array
            {
                return [
                    'scores' => [],
                    'overall' => 0,
                    'reasoning' => [],
                    'kill_condition' => 'hardware_component',
                    'kill_reasoning' => 'Requires physical hardware.',
                    'success_pattern_confidence' => 0,
                    'success_pattern_notes' => null,
                ];
            }
        };

        $idea = Idea::factory()->create(['status' => 'pending']);

        (new ScoreIdeaJob($idea))->handle($agentStub, new CompetitionSearchService());

        $this->assertDatabaseHas('ideas', [
            'id' => $idea->id,
            'status' => 'discarded',
            'kill_condition' => 'hardware_component',
        ]);
    }

    public function test_idea_status_set_to_gate_failed_when_gate_does_not_pass(): void
    {
        $agentStub = new class(new ClaudeCliRunner()) extends ScoringAgentService {
            public function runSpecificityGate(Idea $idea): array
            {
                return [
                    'passed' => false,
                    'answers' => [],
                    'reasoning' => 'Idea is too vague.',
                ];
            }
        };

        $idea = Idea::factory()->create(['status' => 'pending']);

        (new ScoreIdeaJob($idea))->handle($agentStub, new CompetitionSearchService());

        $this->assertDatabaseHas('ideas', [
            'id' => $idea->id,
            'status' => 'gate_failed',
            'specificity_gate_status' => 'failed',
        ]);
    }
}
