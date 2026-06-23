<?php

namespace Database\Factories;

use App\Enums\ComplianceStatus;
use App\Enums\SoftwareStatus;
use App\Models\Software;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Software>
 */
class SoftwareFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(array_map(static fn (SoftwareStatus $status): string => $status->value, SoftwareStatus::cases())),
            'current_version' => null,
            'last_release_date' => null,
            'license_type' => fake()->randomElement(['MIT', 'GPLv3', 'Apache-2.0']),
            'compliance_status' => fake()->randomElement(array_map(static fn (ComplianceStatus $status): string => $status->value, ComplianceStatus::cases())),
            'github_repo_url' => fake()->url(),
        ];
    }
}
