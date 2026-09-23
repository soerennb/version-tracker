<?php

namespace Database\Seeders;

use App\Models\ReleaseComposition;
use Illuminate\Database\Seeder;

class ReleaseCompositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReleaseComposition::factory()->count(2)->create();
    }
}
