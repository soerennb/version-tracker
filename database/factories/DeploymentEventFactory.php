<?php

namespace Database\Factories;

use App\Enums\DeploymentEventType;
use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\DeploymentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeploymentEvent>
 */
class DeploymentEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deployment_id' => Deployment::factory(),
            'actor_id' => User::factory(),
            'type' => DeploymentEventType::CREATED,
            'from_status' => null,
            'to_status' => DeploymentStatus::PLANNED->value,
            'comment' => null,
            'metadata' => null,
            'interface' => 'web',
            'api_token_id' => null,
        ];
    }
}
