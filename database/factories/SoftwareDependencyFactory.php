<?php

namespace Database\Factories;

use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareDependency>
 */
class SoftwareDependencyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'software_id' => Software::factory(),
            'depends_on_software_id' => Software::factory(),
            'applies_to_version_id' => null,
            'min_version_id' => Version::factory(),
            'max_version_id' => Version::factory(),
            'dependency_type' => fake()->randomElement(['runtime', 'build', 'dev']),
        ];
    }
}
