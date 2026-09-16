<?php

namespace App\Services;

use App\Helpers\AuditHelper;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenService
{
    /** @return list<string> */
    public function availableAbilities(User $user): array
    {
        $abilities = array_values(array_diff(config('authorization.abilities'), ['manage_dependencies', 'manage_users', 'manage_settings', 'manage_advanced_settings']));
        $allowed = array_values(array_filter($abilities, fn (string $ability): bool => $user->hasAbility($ability)));
        if ($user->hasAbility('manage_dependencies')) {
            $allowed = [...$allowed, 'view_dependencies', 'create_dependencies', 'edit_dependencies', 'delete_dependencies'];
        }

        return $allowed;
    }

    /** @return list<string> */
    public function preset(User $user, string $preset): array
    {
        return array_values(array_filter($this->availableAbilities($user), fn (string $ability): bool => str_starts_with($ability, 'view_') || $ability === 'download_files' || ($preset === 'edit' && (str_starts_with($ability, 'create_') || str_starts_with($ability, 'edit_') || $ability === 'upload_files'))));
    }

    /** @param array<string,mixed> $data */
    public function create(User $user, array $data): NewAccessToken
    {
        $data = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'], 'access' => ['required', 'array', 'min:1'], 'access.*' => ['required', Rule::in(['mcp', 'rest'])],
            'abilities' => ['required', 'array', 'min:1'], 'abilities.*' => ['required', Rule::in($this->availableAbilities($user))], 'expires_at' => ['required', 'date', 'after:now'],
        ])->validate();
        $token = $user->createToken($data['name'], array_values(array_unique([...array_map(fn (string $access): string => 'access_'.$access, $data['access']), ...$data['abilities']])), new \DateTimeImmutable($data['expires_at']));
        AuditHelper::logAction($user, 'api_token.created', PersonalAccessToken::class, (int) $token->accessToken->id, [], ['name' => $token->accessToken->name, 'abilities' => $token->accessToken->abilities, 'expires_at' => $data['expires_at']]);

        return $token;
    }

    public function revoke(User $actor, PersonalAccessToken $token): void
    {
        if (! $actor->isAdmin() && ($token->tokenable_type !== $actor->getMorphClass() || (int) $token->tokenable_id !== $actor->id)) {
            throw new AuthorizationException;
        }
        AuditHelper::logAction($actor, 'api_token.revoked', PersonalAccessToken::class, (int) $token->id, ['name' => $token->name], []);
        $token->delete();
    }

    public function allows(User $user, string $ability): bool
    {
        $token = $user->currentAccessToken();

        return $token instanceof PersonalAccessToken && $token->exists && $token->tokenable_type === $user->getMorphClass() && (int) $token->tokenable_id === $user->id && (! $token->expires_at || $token->expires_at->isFuture()) && $token->can($ability);
    }

    public function authorize(User $user, string $ability): void
    {
        if (! $this->allows($user, $ability)) {
            throw new AuthorizationException('The API token does not permit '.$ability.'.');
        }
    }
}
