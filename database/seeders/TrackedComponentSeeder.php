<?php

namespace Database\Seeders;

use App\Models\TrackedComponent;
use Illuminate\Database\Seeder;

class TrackedComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['baseline', 'eforms_component', 'eforms_sdk', 'interface'] as $kind) {
            TrackedComponent::factory()->create(['kind' => $kind]);
        }
    }
}
