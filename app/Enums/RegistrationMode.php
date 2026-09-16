<?php

namespace App\Enums;

enum RegistrationMode: string
{
    case Open = 'open';
    case InvitationOnly = 'invitation_only';
    case Disabled = 'disabled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
