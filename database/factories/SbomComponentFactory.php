<?php

namespace Database\Factories;

use App\Models\SbomComponent;
use App\Models\SbomDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SbomComponent>
 */
class SbomComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sbom_document_id' => SbomDocument::factory(),
            'bom_ref' => 'component-'.fake()->unique()->numberBetween(1, 999999),
            'package_type' => 'library',
            'group_name' => null,
            'name' => fake()->slug(2),
            'version' => fake()->numerify('1.##.#'),
            'purl' => 'pkg:generic/'.fake()->slug(2).'@1.0.0',
            'cpe' => null,
            'supplier' => null,
            'licenses' => [],
            'hashes' => [],
            'properties' => [],
        ];
    }
}
