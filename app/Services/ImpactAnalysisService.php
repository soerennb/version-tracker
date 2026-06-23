<?php

namespace App\Services;

use App\Helpers\DependencyHelper;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Support\Collection;

class ImpactAnalysisService
{
    /**
     * @return array<string, mixed>
     */
    public function forSoftware(Software $software): array
    {
        return [
            'type' => 'software',
            'target' => $this->softwarePayload($software),
            'affected_software' => $this->affectedSoftware($software),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forVersion(Version $version): array
    {
        $version->loadMissing('software');

        return [
            'type' => 'version',
            'target' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'software' => $version->software ? $this->softwarePayload($version->software) : null,
            ],
            'affected_software' => $version->software
                ? $this->affectedSoftware($version->software, $version)
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forVulnerability(Vulnerability $vulnerability): array
    {
        $vulnerability->loadMissing('affectedVersion.software');

        return [
            'type' => 'vulnerability',
            'target' => [
                'id' => $vulnerability->id,
                'cve_id' => $vulnerability->cve_id,
                'severity' => $vulnerability->severity?->value,
                'affected_version' => $vulnerability->affectedVersion ? [
                    'id' => $vulnerability->affectedVersion->id,
                    'version_number' => $vulnerability->affectedVersion->version_number,
                    'software' => $vulnerability->affectedVersion->software
                        ? $this->softwarePayload($vulnerability->affectedVersion->software)
                        : null,
                ] : null,
            ],
            'affected_software' => $vulnerability->affectedVersion?->software
                ? $this->affectedSoftware($vulnerability->affectedVersion->software, $vulnerability->affectedVersion)
                : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function affectedSoftware(Software $target, ?Version $targetVersion = null): array
    {
        $results = [];
        $visited = [$target->id => true];

        $this->collectAffectedSoftware($target, $targetVersion, [], 0, $visited, $results);

        return array_values($results);
    }

    /**
     * @param  array<int, array{id:int,name:string}>  $path
     * @param  array<int, bool>  $visited
     * @param  array<int, array<string, mixed>>  $results
     */
    protected function collectAffectedSoftware(Software $target, ?Version $targetVersion, array $path, int $depth, array &$visited, array &$results): void
    {
        $incomingDependencies = SoftwareDependency::query()
            ->with(['software', 'minVersion', 'maxVersion'])
            ->where('depends_on_software_id', $target->id)
            ->get()
            ->filter(fn (SoftwareDependency $dependency): bool => $targetVersion === null || DependencyHelper::isVersionWithinConstraint($targetVersion->version_number, $dependency));

        foreach ($incomingDependencies as $dependency) {
            $dependentSoftware = $dependency->software;

            if (! $dependentSoftware || isset($visited[$dependentSoftware->id])) {
                continue;
            }

            $visited[$dependentSoftware->id] = true;
            $nextPath = [
                ...$path,
                $this->softwarePayload($target),
                $this->softwarePayload($dependentSoftware),
            ];

            $results[$dependentSoftware->id] = [
                'software' => $this->softwarePayload($dependentSoftware),
                'depth' => $depth + 1,
                'via_dependency' => $this->dependencyPayload($dependency),
                'path' => $this->uniquePath($nextPath),
            ];

            $this->collectAffectedSoftware($dependentSoftware, null, $nextPath, $depth + 1, $visited, $results);
        }
    }

    /**
     * @return array{id:int,name:string}
     */
    protected function softwarePayload(Software $software): array
    {
        return [
            'id' => $software->id,
            'name' => $software->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dependencyPayload(SoftwareDependency $dependency): array
    {
        return [
            'id' => $dependency->id,
            'dependency_type' => $dependency->dependency_type,
            'min_version' => $dependency->minVersion?->version_number,
            'max_version' => $dependency->maxVersion?->version_number,
        ];
    }

    /**
     * @param  array<int, array{id:int,name:string}>  $path
     * @return array<int, array{id:int,name:string}>
     */
    protected function uniquePath(array $path): array
    {
        return Collection::make($path)
            ->unique('id')
            ->values()
            ->all();
    }
}
