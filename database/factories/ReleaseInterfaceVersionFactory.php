<?php

namespace Database\Factories;

use App\Models\ComponentVersion;
use App\Models\ReleaseComposition;
use App\Models\ReleaseInterfaceVersion;
use App\Models\TrackedComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReleaseInterfaceVersion>
 */
class ReleaseInterfaceVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'release_composition_id' => ReleaseComposition::factory(),
            'component_version_id' => ComponentVersion::factory()->for(TrackedComponent::factory()->state(['kind' => 'interface']), 'component'),
        ];
    }
}
