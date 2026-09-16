<?php

namespace App\Helpers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;

class AuditHelper
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function logAction(?User $user, string $action, string $modelType, int $modelId, array $oldValues = [], array $newValues = []): void
    {
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'interface' => request()->is('mcp/*') ? 'mcp' : (request()->is('api/*') ? 'rest' : 'web'),
            'api_token_id' => $user?->currentAccessToken() instanceof PersonalAccessToken ? $user->currentAccessToken()->id : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function logModelChange(Model $model, string $action = 'updated'): void
    {
        $original = $model->getOriginal();
        $changes = $model->getChanges();

        self::logAction(
            auth()->user(),
            $action,
            $model::class,
            (int) $model->getKey(),
            $original,
            $changes
        );
    }
}
