<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\VersionStatus;
use App\Models\Software;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Version>
 */
class VersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $releaseDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'software_id' => Software::factory(),
            'version_number' => sprintf('%d.%d.%d', fake()->numberBetween(0, 4), fake()->numberBetween(0, 20), fake()->numberBetween(0, 50)),
            'release_date' => $releaseDate,
            'status' => fake()->randomElement(array_map(static fn (VersionStatus $status): string => $status->value, VersionStatus::cases())),
            'approval_status' => fake()->randomElement(array_map(static fn (ApprovalStatus $status): string => $status->value, ApprovalStatus::cases())),
            'eol_date' => fake()->optional()->dateTimeBetween($releaseDate, '+2 years'),
            'lts_date' => fake()->optional()->dateTimeBetween($releaseDate, '+1 year'),
            'support_status' => fake()->optional()->randomElement(['supported', 'deprecated', 'maintenance']),
        ];
    }
}
