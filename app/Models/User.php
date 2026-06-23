<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'abilities',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'abilities' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->resolveRole() !== UserRole::VIEWER;
    }

    public function isAdmin(): bool
    {
        return $this->resolveRole() === UserRole::ADMIN;
    }

    public function hasAbility(string $ability): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $roleAbilities = config('authorization.role_abilities.'.$this->resolveRole()->value, []);
        if (in_array('*', $roleAbilities, true) || in_array($ability, $roleAbilities, true)) {
            return true;
        }

        $customAbilities = is_array($this->abilities) ? $this->abilities : [];

        return in_array($ability, $customAbilities, true);
    }

    protected function resolveRole(): UserRole
    {
        return $this->role instanceof UserRole ? $this->role : UserRole::VIEWER;
    }

    public function createdSoftware(): HasMany
    {
        return $this->hasMany(Software::class, 'created_by');
    }

    public function updatedSoftware(): HasMany
    {
        return $this->hasMany(Software::class, 'updated_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
