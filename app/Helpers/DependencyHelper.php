<?php

namespace App\Helpers;

use App\Models\Software;
use App\Models\SoftwareDependency;

class DependencyHelper
{
    public static function validateDependency(SoftwareDependency $dependency): bool
    {
        if (! $dependency->dependsOnSoftware?->status?->isActive()) {
            return false;
        }

        if (! $dependency->hasVersionConstraint()) {
            return true;
        }

        $availableVersions = $dependency->dependsOnSoftware
            ?->versions()
            ->where('status', 'published')
            ->pluck('version_number')
            ->all() ?? [];

        foreach ($availableVersions as $version) {
            if (self::isVersionWithinConstraint($version, $dependency)) {
                return true;
            }
        }

        return false;
    }

    public static function isVersionWithinConstraint(string $version, SoftwareDependency $dependency): bool
    {
        $minVersion = $dependency->minVersion?->version_number;
        $maxVersion = $dependency->maxVersion?->version_number;

        return VersionHelper::isVersionInRange($version, $minVersion, $maxVersion);
    }

    public static function wouldCreateCycle(int $softwareId, int $dependsOnSoftwareId, ?int $ignoreDependencyId = null): bool
    {
        return self::hasDependencyPath($dependsOnSoftwareId, $softwareId, $ignoreDependencyId);
    }

    public static function hasDependencyPath(int $fromSoftwareId, int $toSoftwareId, ?int $ignoreDependencyId = null): bool
    {
        return self::searchDependencyPath($fromSoftwareId, $toSoftwareId, $ignoreDependencyId, []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDependencyTree(Software $software): array
    {
        $dependencies = [];
        $visited = [];

        self::resolveDependencies($software, $dependencies, $visited);

        return $dependencies;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dependencies
     * @param  array<int, bool>  $visited
     */
    protected static function resolveDependencies(Software $software, array &$dependencies, array &$visited): void
    {
        if (isset($visited[$software->id])) {
            return;
        }

        $visited[$software->id] = true;

        foreach ($software->dependenciesOutgoing as $dependency) {
            $dependencies[] = [
                'software' => $dependency->dependsOnSoftware,
                'dependency' => $dependency,
                'sub_dependencies' => self::getDependencyTree($dependency->dependsOnSoftware),
            ];
        }
    }

    /**
     * @param  array<int, bool>  $visited
     */
    protected static function searchDependencyPath(int $fromSoftwareId, int $toSoftwareId, ?int $ignoreDependencyId, array $visited): bool
    {
        if ($fromSoftwareId === $toSoftwareId) {
            return true;
        }

        if (isset($visited[$fromSoftwareId])) {
            return false;
        }

        $visited[$fromSoftwareId] = true;

        $nextSoftwareIds = SoftwareDependency::query()
            ->where('software_id', $fromSoftwareId)
            ->when($ignoreDependencyId, fn ($query) => $query->whereKeyNot($ignoreDependencyId))
            ->pluck('depends_on_software_id');

        foreach ($nextSoftwareIds as $nextSoftwareId) {
            if (self::searchDependencyPath((int) $nextSoftwareId, $toSoftwareId, $ignoreDependencyId, $visited)) {
                return true;
            }
        }

        return false;
    }
}
