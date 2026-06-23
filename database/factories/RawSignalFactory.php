<?php

namespace Database\Factories;

use App\Models\RawSignal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RawSignal>
 */
class RawSignalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $source = fake()->randomElement(['reddit', 'hackernews']);

        return [
            'source' => $source,
            'source_id' => fake()->unique()->lexify('??????????'),
            'source_url' => fake()->url(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'author' => fake()->userName(),
            'score' => fake()->numberBetween(0, 5000),
            'comment_count' => fake()->numberBetween(0, 500),
            'category' => $source === 'reddit' ? fake()->randomElement(['SaaS', 'indiehackers', 'laravel']) : fake()->randomElement(['ask_hn', 'show_hn']),
            'metadata' => [],
            'processed' => false,
            'flagged' => false,
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function reddit(): static
    {
        return $this->state(fn () => [
            'source' => 'reddit',
            'category' => fake()->randomElement(['SaaS', 'indiehackers', 'microsaas', 'laravel']),
            'metadata' => ['subreddit' => 'SaaS', 'is_self' => true],
        ]);
    }

    public function hackernews(): static
    {
        return $this->state(fn () => [
            'source' => 'hackernews',
            'category' => fake()->randomElement(['ask_hn', 'show_hn']),
            'metadata' => ['_tags' => ['ask_hn', 'story']],
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn () => ['processed' => true]);
    }

    public function flagged(): static
    {
        return $this->state(fn () => ['flagged' => true]);
    }
}
