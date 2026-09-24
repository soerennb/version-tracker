<?php

namespace Database\Factories;

use App\Enums\VersionStatus;
use App\Models\ComponentVersion;
use App\Models\ReleaseComposition;
use App\Models\Software;
use App\Models\TrackedComponent;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReleaseComposition>
 */
class ReleaseCompositionFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(fn (ReleaseComposition $composition) => $composition->supportedInterfaces()->create([
            'component_version_id' => $composition->active_eforms_sdk_version_id,
        ]));
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version_id' => Version::factory()->for(Software::factory()->state(['tracks_release_composition' => true]))->state(['status' => VersionStatus::DRAFT]),
            'baseline_version_id' => ComponentVersion::factory()->for(TrackedComponent::factory()->state(['kind' => 'baseline']), 'component'),
            'eforms_component_version_id' => ComponentVersion::factory()->for(TrackedComponent::factory()->state(['kind' => 'eforms_component']), 'component'),
            'active_eforms_sdk_version_id' => ComponentVersion::factory()->for(TrackedComponent::factory()->state(['kind' => 'eforms_sdk']), 'component'),
        ];
    }
}
