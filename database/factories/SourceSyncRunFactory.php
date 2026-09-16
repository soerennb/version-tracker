<?php

namespace Database\Factories;

use App\Enums\SourceSyncStatus;
use App\Models\Software;
use App\Models\SourceSyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourceSyncRun>
 */
class SourceSyncRunFactory extends Factory
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
            'provider' => 'github',
            'status' => SourceSyncStatus::SUCCEEDED,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'created_count' => 1,
            'updated_count' => 0,
            'unchanged_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'error_message' => null,
            'errors' => [],
        ];
    }
}
