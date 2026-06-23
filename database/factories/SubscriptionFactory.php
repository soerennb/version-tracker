<?php

namespace Database\Factories;

use App\Enums\SubscriptionEvent;
use App\Models\Software;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
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
            'software_id' => Software::factory(),
            'event' => fake()->randomElement(array_map(static fn (SubscriptionEvent $event): string => $event->value, SubscriptionEvent::cases())),
        ];
    }
}
