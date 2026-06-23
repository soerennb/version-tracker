<?php

namespace App\Enums;

enum SupportStatus: string
{
    case SUPPORTED = 'supported';
    case MAINTENANCE = 'maintenance';
    case DEPRECATED = 'deprecated';
    case EOL = 'eol';

    public function label(): string
    {
        return match ($this) {
            self::SUPPORTED => __('versions.support.supported'),
            self::MAINTENANCE => __('versions.support.maintenance'),
            self::DEPRECATED => __('versions.support.deprecated'),
            self::EOL => __('versions.support.eol'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUPPORTED => 'success',
            self::MAINTENANCE => 'warning',
            self::DEPRECATED => 'danger',
            self::EOL => 'gray',
        };
    }
}
