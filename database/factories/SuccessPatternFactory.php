<?php

namespace Database\Factories;

use App\Models\SuccessPattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuccessPattern>
 */
class SuccessPatternFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_name' => $this->faker->company(),
            'revenue_milestone' => '$1K MRR',
            'mrr_amount' => $this->faker->numberBetween(1000, 5000),
            'category' => $this->faker->randomElement(['developer-tools', 'freelancer', 'saas-ops', 'content', 'analytics']),
            'description' => $this->faker->sentence(),
            'pain_solved' => $this->faker->sentence(),
            'target_customer' => $this->faker->jobTitle(),
            'pricing_model' => $this->faker->randomElement(['subscription', 'one-time', 'usage']),
            'key_insight' => $this->faker->sentence(),
            'source' => $this->faker->randomElement(['indie_hackers', 'reddit', 'manual']),
        ];
    }
}
