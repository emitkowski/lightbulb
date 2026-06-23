<?php

namespace Database\Factories;

use App\Models\IngestionRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngestionRun>
 */
class IngestionRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $found = fake()->numberBetween(0, 25);
        $inserted = fake()->numberBetween(0, $found);

        return [
            'source' => fake()->randomElement(['reddit', 'hackernews']),
            'query' => fake()->sentence(4),
            'signals_found' => $found,
            'signals_inserted' => $inserted,
            'signals_skipped' => $found - $inserted,
            'status' => 'success',
            'error_message' => null,
            'duration_ms' => fake()->numberBetween(200, 5000),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'signals_found' => 0,
            'signals_inserted' => 0,
            'signals_skipped' => 0,
            'error_message' => fake()->sentence(),
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn () => ['status' => 'partial']);
    }
}
