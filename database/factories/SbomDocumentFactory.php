<?php

namespace Database\Factories;

use App\Models\SbomDocument;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SbomDocument>
 */
class SbomDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version_id' => Version::factory(),
            'uploaded_by' => null,
            'filename' => 'bom.json',
            'format' => 'cyclonedx',
            'spec_version' => '1.6',
            'serial_number' => 'urn:uuid:'.fake()->uuid(),
            'document_hash' => hash('sha256', fake()->unique()->uuid()),
            'idempotency_key' => null,
            'source' => 'manual',
            'status' => 'processed',
            'component_count' => 0,
            'finding_count' => 0,
            'parsed_at' => now(),
            'last_enriched_at' => null,
            'enrichment_status' => 'pending',
            'enrichment_error' => null,
            'error_message' => null,
            'payload' => '{}',
        ];
    }
}
