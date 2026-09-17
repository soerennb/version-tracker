<?php

namespace Database\Factories;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deployment>
 */
class DeploymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'software_id' => Software::factory(),
            'version_id' => Version::factory(),
            'environment_id' => Environment::factory(),
            'status' => DeploymentStatus::PLANNED,
            'scheduled_at' => now()->addDay(),
            'approved_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'created_by' => User::factory(),
            'approved_by' => null,
            'executed_by' => null,
            'change_reference' => 'CHG-'.fake()->unique()->numberBetween(1000, 9999),
            'maintenance_window_start' => null,
            'maintenance_window_end' => null,
            'external_reference' => null,
            'source' => 'web',
            'notes' => fake()->optional()->sentence(),
            'result' => null,
            'relation_type' => null,
            'related_deployment_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Deployment $deployment): void {
            $deployment->forceFill([
                'software_id' => $deployment->version()->value('software_id'),
            ])->saveQuietly();
        });
    }
}
