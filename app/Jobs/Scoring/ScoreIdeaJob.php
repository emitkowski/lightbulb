<?php

namespace App\Jobs\Scoring;

use Throwable;
use App\Models\Idea;
use App\Services\Scoring\CompetitionSearchService;
use App\Services\Scoring\ScoringAgentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScoreIdeaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        protected Idea $idea
    ) {}

    public function handle(ScoringAgentService $agent, CompetitionSearchService $competition): void
    {
        if (! in_array($this->idea->status, ['pending'])) {
            return;
        }

        $this->idea->update(['status' => 'scoring']);

        try {
            // Step 1: Specificity Gate
            $gate = $agent->runSpecificityGate($this->idea);

            $this->idea->update([
                'specificity_gate_status' => $gate['passed'] ? 'passed' : 'failed',
                'specificity_gate_answers' => $gate['answers'],
                'specificity_gate_reasoning' => $gate['reasoning'],
            ]);

            if (! $gate['passed']) {
                $this->idea->update(['status' => 'gate_failed', 'processed_at' => now()]);
                Log::info('ScoreIdeaJob: idea failed specificity gate', ['idea_id' => $this->idea->id]);

                return;
            }

            // Step 2: Competition search
            $competitionData = $competition->search($this->idea->title);

            $this->idea->update([
                'competition_query' => $this->idea->title,
                'competition_results' => $competitionData['results'],
                'competition_summary' => $competitionData['summary'],
            ]);

            // Step 3: 6-dimension scoring
            $result = $agent->scoreIdea($this->idea->fresh(), $competitionData);

            if ($result['kill_condition']) {
                $this->idea->update([
                    'status' => 'discarded',
                    'kill_condition' => $result['kill_condition'],
                    'kill_reasoning' => $result['kill_reasoning'],
                    'processed_at' => now(),
                ]);
                Log::info('ScoreIdeaJob: idea discarded by kill condition', [
                    'idea_id' => $this->idea->id,
                    'condition' => $result['kill_condition'],
                ]);

                return;
            }

            $this->idea->update([
                'score_problem_strength' => $result['scores']['problem_strength'] ?? null,
                'score_distribution_path' => $result['scores']['distribution_path'] ?? null,
                'score_competition_gap' => $result['scores']['competition_gap'] ?? null,
                'score_build_feasibility' => $result['scores']['build_feasibility'] ?? null,
                'score_automability' => $result['scores']['automability'] ?? null,
                'score_revenue_plausibility' => $result['scores']['revenue_plausibility'] ?? null,
                'score_overall' => $result['overall'],
                'score_reasoning' => $result['reasoning'],
                'success_pattern_confidence' => $result['success_pattern_confidence'] ?? null,
                'success_pattern_notes' => $result['success_pattern_notes'] ?? null,
                'status' => 'scored',
                'processed_at' => now(),
            ]);

            Log::info('ScoreIdeaJob complete', [
                'idea_id' => $this->idea->id,
                'score_overall' => $result['overall'],
            ]);

        } catch (Throwable $e) {
            Log::error('ScoreIdeaJob failed', ['idea_id' => $this->idea->id, 'error' => $e->getMessage()]);
            $this->idea->update(['status' => 'pending']); // reset for retry
            throw $e;
        }
    }
}
