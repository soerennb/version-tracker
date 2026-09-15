<?php

namespace Database\Factories;

use App\Models\EolAlertDelivery;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EolAlertDelivery>
 */
class EolAlertDeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version_id' => Version::factory(),
            'eol_date' => now()->addDays(30)->toDateString(),
            'window_days' => 30,
            'dispatched_at' => now(),
        ];
    }
}
