<?php

namespace App\Enums;

enum InvitationStatus: string
{
    case OPEN = 'open';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case ACCEPTED = 'accepted';

    public function label(): string
    {
        return __('filament.users.invitation_status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::EXPIRED => 'warning',
            self::REVOKED => 'danger',
            self::ACCEPTED => 'gray',
        };
    }
}
