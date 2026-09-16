<?php

namespace Database\Factories;

use App\Enums\VersionSourceKind;
use App\Models\Software;
use App\Models\Version;
use App\Models\VersionSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VersionSource>
 */
class VersionSourceFactory extends Factory
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
            'provider' => 'github',
            'source_kind' => VersionSourceKind::RELEASE,
            'external_id' => (string) fake()->unique()->numberBetween(1, 999999),
            'tag_name' => 'v'.fake()->unique()->semver(),
            'name' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'source_url' => fake()->url(),
            'source_updated_at' => now(),
            'payload_hash' => hash('sha256', fake()->sentence()),
            'imported_content_hash' => hash('sha256', fake()->sentence()),
            'is_prerelease' => false,
            'last_seen_at' => now(),
        ];
    }
}
