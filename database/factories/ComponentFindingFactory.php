<?php

namespace Database\Factories;

use App\Enums\ExploitabilityStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\ComponentFinding;
use App\Models\SbomComponent;
use App\Models\SbomDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponentFinding>
 */
class ComponentFindingFactory extends Factory
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
            'sbom_component_id' => SbomComponent::factory(),
            'external_id' => 'CVE-'.fake()->year().'-'.fake()->unique()->numberBetween(1000, 999999),
            'source' => 'OSV',
            'severity' => VulnerabilitySeverity::HIGH->value,
            'cvss_score' => 8.0,
            'epss_score' => null,
            'is_kev' => false,
            'risk_score' => null,
            'risk_factors' => [],
            'exploitability' => ExploitabilityStatus::UNKNOWN->value,
            'status' => VulnerabilityStatus::OPEN->value,
            'description' => fake()->sentence(),
            'affected_range' => null,
            'fixed_version' => null,
            'source_url' => null,
            'details' => [],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'resolved_at' => null,
        ];
    }
}
