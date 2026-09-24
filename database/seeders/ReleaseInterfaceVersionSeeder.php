<?php

namespace Database\Seeders;

use App\Models\ComponentVersion;
use App\Models\ReleaseComposition;
use App\Models\ReleaseInterfaceVersion;
use Illuminate\Database\Seeder;

class ReleaseInterfaceVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ReleaseComposition::query()->get() as $composition) {
            $interface = ComponentVersion::factory()->create();
            ReleaseInterfaceVersion::factory()->for($composition, 'composition')->for($interface, 'componentVersion')->create();
        }
    }
}
