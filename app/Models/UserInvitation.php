<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Database\Factories\UserInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    /** @use HasFactory<UserInvitationFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'name',
        'token_hash',
        'invited_by',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isUsable(): bool
    {
        return $this->status() === InvitationStatus::OPEN;
    }

    public function status(): InvitationStatus
    {
        return match (true) {
            $this->accepted_at !== null => InvitationStatus::ACCEPTED,
            $this->revoked_at !== null => InvitationStatus::REVOKED,
            $this->expires_at?->isPast() => InvitationStatus::EXPIRED,
            default => InvitationStatus::OPEN,
        };
    }
}
