<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'user:set-role
                            {user : User ID or email address}
                            {role : admin, editor, or viewer}
                            {--abilities= : Comma-separated custom abilities to set}';

    protected $description = 'Set role and optional custom abilities for an existing user.';

    public function handle(): int
    {
        $identifier = (string) $this->argument('user');
        $roleValue = strtolower((string) $this->argument('role'));
        $abilitiesOption = trim((string) $this->option('abilities'));

        $user = User::query()
            ->when(is_numeric($identifier), fn ($query) => $query->whereKey((int) $identifier))
            ->when(! is_numeric($identifier), fn ($query) => $query->where('email', $identifier))
            ->first();

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $role = UserRole::tryFrom($roleValue);
        if (! $role) {
            $this->error('Invalid role. Allowed: admin, editor, viewer.');

            return self::FAILURE;
        }

        $abilities = null;
        if ($abilitiesOption !== '') {
            $validAbilities = config('authorization.abilities', []);
            $parsedAbilities = array_values(array_unique(array_filter(array_map(
                static fn (string $ability): string => trim($ability),
                explode(',', $abilitiesOption)
            ))));

            $invalidAbilities = array_values(array_diff($parsedAbilities, $validAbilities));
            if (! empty($invalidAbilities)) {
                $this->error('Invalid abilities: '.implode(', ', $invalidAbilities));

                return self::FAILURE;
            }

            $abilities = $parsedAbilities;
        }

        $user->forceFill([
            'role' => $role,
            'abilities' => $abilities,
        ])->save();

        $this->info(sprintf(
            'Updated user %s (%s) to role %s.',
            (string) $user->id,
            (string) $user->email,
            $role->value
        ));

        return self::SUCCESS;
    }
}
