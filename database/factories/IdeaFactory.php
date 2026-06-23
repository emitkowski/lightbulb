<?php

namespace Database\Factories;

use App\Models\Idea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Idea>
 */
class IdeaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'signals_summary' => $this->faker->paragraph(),
            'source_signals_count' => $this->faker->numberBetween(2, 20),
            'status' => 'pending',
        ];
    }

    public function scored(): static
    {
        return $this->state(function () {
            $overall = $this->faker->numberBetween(30, 90);

            return [
                'status' => 'scored',
                'specificity_gate_status' => 'passed',
                'score_problem_strength' => $this->faker->numberBetween(40, 100),
                'score_distribution_path' => $this->faker->numberBetween(40, 100),
                'score_competition_gap' => $this->faker->numberBetween(40, 100),
                'score_build_feasibility' => $this->faker->numberBetween(40, 100),
                'score_automability' => $this->faker->numberBetween(40, 100),
                'score_revenue_plausibility' => $this->faker->numberBetween(40, 100),
                'score_overall' => $overall,
                'score_reasoning' => [
                    'problem_strength' => $this->faker->sentence(),
                    'distribution_path' => $this->faker->sentence(),
                    'competition_gap' => $this->faker->sentence(),
                    'build_feasibility' => $this->faker->sentence(),
                    'automability' => $this->faker->sentence(),
                    'revenue_plausibility' => $this->faker->sentence(),
                ],
                'processed_at' => now(),
            ];
        });
    }

    public function gateFailed(): static
    {
        return $this->state(fn () => [
            'status' => 'gate_failed',
            'specificity_gate_status' => 'failed',
            'specificity_gate_reasoning' => $this->faker->sentence(),
        ]);
    }

    public function discarded(): static
    {
        return $this->state(fn () => [
            'status' => 'discarded',
            'kill_condition' => $this->faker->randomElement(['requires_sales_calls', 'hardware_component', 'ad_supported_content']),
            'kill_reasoning' => $this->faker->sentence(),
        ]);
    }
}
