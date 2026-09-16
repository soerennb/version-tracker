<?php

namespace Database\Factories;

use App\Models\FileAttachment;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileAttachment>
 */
class FileAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version_id' => Version::factory(),
            'filename' => fake()->uuid().'.pdf',
            'artifact_type' => 'release',
            'platform' => null,
            'architecture' => null,
            'file_path' => 'attachments/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 1_000_000),
            'checksum' => null,
            'checksum_algorithm' => null,
            'signature' => null,
            'verification_status' => 'unverified',
            'is_public' => true,
        ];
    }
}
