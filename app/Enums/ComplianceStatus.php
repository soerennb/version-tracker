<?php

namespace App\Enums;

enum ComplianceStatus: string
{
    case COMPLIANT = 'compliant';
    case NON_COMPLIANT = 'non_compliant';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::COMPLIANT => __('filament.software.compliance.compliant'),
            self::NON_COMPLIANT => __('filament.software.compliance.non_compliant'),
            self::UNKNOWN => __('filament.software.compliance.unknown'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMPLIANT => 'success',
            self::NON_COMPLIANT => 'danger',
            self::UNKNOWN => 'gray',
        };
    }
}
