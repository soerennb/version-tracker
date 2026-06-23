<?php

namespace Database\Factories;

use App\Enums\RejectReason;
use App\Enums\ReviewAction;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VersionReview>
 */
class VersionReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version_id' => Version::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(array_map(static fn (ReviewAction $action): string => $action->value, ReviewAction::cases())),
            'reject_reason' => fake()->optional()->randomElement(array_map(static fn (RejectReason $reason): string => $reason->value, RejectReason::cases())),
            'comment' => fake()->optional()->paragraph(),
            'metadata' => null,
        ];
    }
}
