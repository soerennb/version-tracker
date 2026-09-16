<?php

namespace Database\Factories;

use App\Models\ReleaseException;
use App\Models\User;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReleaseException>
 */
class ReleaseExceptionFactory extends Factory
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
            'owner_id' => User::factory(),
            'approved_by' => User::factory(),
            'check_code' => 'blocking_vulnerabilities',
            'reason' => 'Risk accepted until remediation is deployed.',
            'expires_at' => now()->addDays(30),
            'approved_at' => now(),
            'revoked_at' => null,
        ];
    }
}
