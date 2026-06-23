<?php

namespace App\Services;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\SoftwareDependency;
use Illuminate\Support\Collection;

class DependencyMapService
{
    /**
     * @return array{selected_id:?int,nodes:array<int, array<string, mixed>>,edges:array<int, array<string, mixed>>,stats:array<string, int>}
     */
    public function build(?int $selectedSoftwareId = null): array
    {
        $selectedSoftware = $selectedSoftwareId
            ? Software::query()->find($selectedSoftwareId)
            : Software::query()->orderBy('name')->first();

        if (! $selectedSoftware) {
            return [
                'selected_id' => null,
                'nodes' => [],
                'edges' => [],
                'stats' => [
                    'nodes' => 0,
                    'edges' => 0,
                    'vulnerable' => 0,
                    'eol' => 0,
                ],
            ];
        }

        $dependencies = SoftwareDependency::query()
            ->with(['software.versions.vulnerabilities', 'dependsOnSoftware.versions.vulnerabilities', 'minVersion', 'maxVersion'])
            ->where('software_id', $selectedSoftware->id)
            ->orWhere('depends_on_software_id', $selectedSoftware->id)
            ->get();

        $software = $this->softwareForMap($selectedSoftware, $dependencies);
        $nodes = $this->nodes($software, $selectedSoftware->id);
        $edges = $this->edges($dependencies, $selectedSoftware->id);

        return [
            'selected_id' => $selectedSoftware->id,
            'nodes' => $nodes,
            'edges' => $edges,
            'stats' => [
                'nodes' => count($nodes),
                'edges' => count($edges),
                'vulnerable' => collect($nodes)->where('has_vulnerability', true)->count(),
                'eol' => collect($nodes)->where('has_eol_risk', true)->count(),
            ],
        ];
    }

    /**
     * @param  Collection<int, SoftwareDependency>  $dependencies
     * @return Collection<int, Software>
     */
    protected function softwareForMap(Software $selectedSoftware, Collection $dependencies): Collection
    {
        return collect([$selectedSoftware])
            ->merge($dependencies->pluck('software')->filter())
            ->merge($dependencies->pluck('dependsOnSoftware')->filter())
            ->unique('id')
            ->sortBy(fn (Software $software): string => $software->id === $selectedSoftware->id ? '' : $software->name)
            ->values();
    }

    /**
     * @param  Collection<int, Software>  $software
     * @return array<int, array<string, mixed>>
     */
    protected function nodes(Collection $software, int $selectedSoftwareId): array
    {
        $count = max($software->count(), 1);
        $radius = $count <= 4 ? 190 : 230;

        return $software
            ->values()
            ->map(function (Software $software, int $index) use ($count, $radius, $selectedSoftwareId): array {
                if ($software->id === $selectedSoftwareId) {
                    $x = 360;
                    $y = 260;
                } else {
                    $angle = (($index - 1) / max($count - 1, 1)) * (2 * pi()) - (pi() / 2);
                    $x = 360 + (cos($angle) * $radius);
                    $y = 260 + (sin($angle) * min($radius, 190));
                }

                return [
                    'id' => $software->id,
                    'name' => $software->name,
                    'status' => $software->status?->value,
                    'status_label' => $software->status?->label(),
                    'x' => (int) round($x),
                    'y' => (int) round($y),
                    'selected' => $software->id === $selectedSoftwareId,
                    'has_vulnerability' => $this->hasOpenHighVulnerability($software),
                    'has_eol_risk' => $this->hasEolRisk($software),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, SoftwareDependency>  $dependencies
     * @return array<int, array<string, mixed>>
     */
    protected function edges(Collection $dependencies, int $selectedSoftwareId): array
    {
        return $dependencies
            ->map(fn (SoftwareDependency $dependency): array => [
                'id' => $dependency->id,
                'from' => $dependency->software_id,
                'to' => $dependency->depends_on_software_id,
                'direction' => $dependency->software_id === $selectedSoftwareId ? 'outgoing' : 'incoming',
                'type' => $dependency->dependency_type,
                'min_version' => $dependency->minVersion?->version_number,
                'max_version' => $dependency->maxVersion?->version_number,
            ])
            ->values()
            ->all();
    }

    protected function hasOpenHighVulnerability(Software $software): bool
    {
        return $software->versions
            ->flatMap(fn ($version) => $version->vulnerabilities)
            ->contains(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
                && in_array($vulnerability->severity, [
                    VulnerabilitySeverity::CRITICAL,
                    VulnerabilitySeverity::HIGH,
                ], true));
    }

    protected function hasEolRisk(Software $software): bool
    {
        return $software->versions
            ->contains(fn ($version): bool => $version->status === VersionStatus::PUBLISHED
                && $version->eol_date !== null
                && $version->eol_date->lte(now()->addDays(90)));
    }
}
