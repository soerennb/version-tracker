<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserInvitation>
 */
class UserInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'token_hash' => hash('sha256', fake()->unique()->regexify('[A-Za-z0-9]{64}')),
            'invited_by' => User::factory()->admin(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'revoked_at' => null,
        ];
    }
}
