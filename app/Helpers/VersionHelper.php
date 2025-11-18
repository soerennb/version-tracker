<?php

namespace App\Helpers;

class VersionHelper
{
    /**
     * @return array<int, int>
     */
    public static function parseVersion(string $version): array
    {
        $parts = explode('.', $version);

        return array_map(static fn (string $segment): int => (int) $segment, $parts);
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

        return 0;
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
}
