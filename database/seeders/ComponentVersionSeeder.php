<?php

namespace Database\Seeders;

use App\Models\ComponentVersion;
use App\Models\TrackedComponent;
use Illuminate\Database\Seeder;

class ComponentVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (TrackedComponent::query()->get() as $component) {
            ComponentVersion::factory()->for($component, 'component')->create([
                'version_label' => $component->kind === 'eforms_sdk' ? '1.13.0' : '1.0.0',
            ]);
        }
    }
}
