<?php

namespace App\Enums;

enum Language: string
{
    case DE = 'de';
    case EN = 'en';

    public function label(): string
    {
        return match ($this) {
            self::DE => __('languages.de'),
            self::EN => __('languages.en'),
        };
    }

    public function nativeLabel(): string
    {
        return match ($this) {
            self::DE => 'Deutsch',
            self::EN => 'English',
        };
    }

    public function isGerman(): bool
    {
        return $this === self::DE;
    }

    public function isEnglish(): bool
    {
        return $this === self::EN;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
