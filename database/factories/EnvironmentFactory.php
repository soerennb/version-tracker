<?php

namespace Database\Factories;

use App\Models\Environment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Environment>
 */
class EnvironmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->slug(2),
            'description' => fake()->optional()->sentence(),
            'is_production' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
