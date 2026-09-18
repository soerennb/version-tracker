<?php

namespace App\Settings;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelSettings\Settings;

class InstallationState extends Settings
{
    public string $profile = 'uninitialized';

    public ?string $setup_completed_at = null;

    public static function group(): string
    {
        return 'installation';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'profile' => 'uninitialized',
            'setup_completed_at' => null,
        ];
    }

    public function isCompleted(): bool
    {
        return filled($this->setup_completed_at);
    }

    public function complete(string $profile = 'standard'): void
    {
        $this->fill([
            'profile' => $profile,
            'setup_completed_at' => now()->toIso8601String(),
        ])->save();
    }

    public function completedAt(): ?CarbonInterface
    {
        return $this->setup_completed_at === null
            ? null
            : CarbonImmutable::parse($this->setup_completed_at);
    }
}
