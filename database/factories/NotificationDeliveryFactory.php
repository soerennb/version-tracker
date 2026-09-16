<?php

namespace Database\Factories;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_key' => 'release-published:'.fake()->unique()->numberBetween(1, 999999),
            'channel' => 'mail,database',
            'status' => NotificationDeliveryStatus::SENT,
            'attempts' => 1,
            'queued_at' => now()->subMinute(),
            'sent_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ];
    }
}
