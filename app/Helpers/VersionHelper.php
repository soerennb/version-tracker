<?php

namespace App\Helpers;

class VersionHelper
{
    public const SEMVER_PATTERN = '^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9A-Za-z-][0-9A-Za-z-]*)(?:\.(?:0|[1-9A-Za-z-][0-9A-Za-z-]*))*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$';

    /**
     * @return array<int, int>
     */
    public static function parseVersion(string $version): array
    {
        $parts = explode('.', self::coreVersion($version));

        return array_map(static fn (string $segment): int => (int) $segment, $parts);
    }

    public static function semverRegex(): string
    {
        return '/'.self::SEMVER_PATTERN.'/';
    }

    public static function isValidSemver(string $version): bool
    {
        return preg_match(self::semverRegex(), $version) === 1;
    }

    public static function compareVersions(string $versionA, string $versionB): int
    {
        $a = self::parseVersion($versionA);
        $b = self::parseVersion($versionB);

        $maxLength = max(count($a), count($b));
        $a = array_pad($a, $maxLength, 0);
        $b = array_pad($b, $maxLength, 0);

        for ($i = 0; $i < $maxLength; $i++) {
            if ($a[$i] < $b[$i]) {
                return -1;
            }

            if ($a[$i] > $b[$i]) {
                return 1;
            }
        }

        return self::comparePreRelease(self::preRelease($versionA), self::preRelease($versionB));
    }

    public static function isVersionInRange(string $version, ?string $minVersion, ?string $maxVersion): bool
    {
        if ($minVersion && self::compareVersions($version, $minVersion) < 0) {
            return false;
        }

        if ($maxVersion && self::compareVersions($version, $maxVersion) > 0) {
            return false;
        }

        return true;
    }

    public static function getNextVersion(string $currentVersion, string $increment = 'patch'): string
    {
        $parts = self::parseVersion($currentVersion);
        $parts = array_pad($parts, 3, 0);

        return match ($increment) {
            'major' => implode('.', [$parts[0] + 1, 0, 0]),
            'minor' => implode('.', [$parts[0], $parts[1] + 1, 0]),
            default => implode('.', [$parts[0], $parts[1], $parts[2] + 1]),
        };
    }

    protected static function coreVersion(string $version): string
    {
        return preg_replace('/[-+].*$/', '', $version) ?: $version;
    }

    protected static function preRelease(string $version): ?string
    {
        if (preg_match('/^[^+-]+-([^+]+)(?:\+.*)?$/', $version, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    protected static function comparePreRelease(?string $preReleaseA, ?string $preReleaseB): int
    {
        if ($preReleaseA === $preReleaseB) {
            return 0;
        }

        if ($preReleaseA === null) {
            return 1;
        }

        if ($preReleaseB === null) {
            return -1;
        }

        $identifiersA = explode('.', $preReleaseA);
        $identifiersB = explode('.', $preReleaseB);
        $maxLength = max(count($identifiersA), count($identifiersB));

        for ($i = 0; $i < $maxLength; $i++) {
            $identifierA = $identifiersA[$i] ?? null;
            $identifierB = $identifiersB[$i] ?? null;

            if ($identifierA === null) {
                return -1;
            }

            if ($identifierB === null) {
                return 1;
            }

            $comparison = self::comparePreReleaseIdentifier($identifierA, $identifierB);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    protected static function comparePreReleaseIdentifier(string $identifierA, string $identifierB): int
    {
        $isNumericA = ctype_digit($identifierA);
        $isNumericB = ctype_digit($identifierB);

        if ($isNumericA && $isNumericB) {
            return (int) $identifierA <=> (int) $identifierB;
        }

        if ($isNumericA) {
            return -1;
        }

        if ($isNumericB) {
            return 1;
        }

        return $identifierA <=> $identifierB;
    }
}
