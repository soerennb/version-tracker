<?php

namespace Database\Factories;

use App\Models\ComponentVersion;
use App\Models\TrackedComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentVersion>
 */
class ComponentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracked_component_id' => TrackedComponent::factory(),
            'version_label' => fake()->numerify('#.#.#'),
            'notes' => null,
        ];
    }
}
