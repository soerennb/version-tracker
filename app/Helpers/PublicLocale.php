<?php

namespace App\Helpers;

use App\Enums\Language;
use App\Models\TextContent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicLocale
{
    public static function requested(Request $request): Language
    {
        return Language::tryFrom($request->string('locale')->toString())
            ?? Language::tryFrom((string) config('app.locale'))
            ?? Language::DE;
    }

    public static function content(Collection $contents, Request $request): ?TextContent
    {
        $requested = self::requested($request);

        return $contents->first(fn (TextContent $content): bool => self::languageValue($content) === $requested->value)
            ?? $contents->first();
    }

    public static function languageValue(TextContent $content): ?string
    {
        return $content->language instanceof Language
            ? $content->language->value
            : (is_string($content->language) ? $content->language : null);
    }

    public static function fallbackUsed(Collection $contents, Request $request): bool
    {
        $content = self::content($contents, $request);

        return $content !== null && self::languageValue($content) !== self::requested($request)->value;
    }
}
