<?php

namespace App\Services;

use App\Models\SoftwareDependency;
use App\Models\Version;
use Illuminate\Support\Collection;

class VersionComparisonService
{
    /**
     * @return array<string, mixed>
     */
    public function compare(Version $left, Version $right): array
    {
        $left->loadMissing(['software', 'textContents', 'fileAttachments', 'vulnerabilities']);
        $right->loadMissing(['software', 'textContents', 'fileAttachments', 'vulnerabilities']);

        $leftDependencies = $this->dependenciesFor($left);
        $rightDependencies = $this->dependenciesFor($right);

        return [
            'product' => ['id' => $left->software_id, 'name' => $left->software?->name],
            'left' => $this->versionPayload($left, $leftDependencies),
            'right' => $this->versionPayload($right, $rightDependencies),
            'dependency_changes' => $this->dependencyChanges($leftDependencies, $rightDependencies),
        ];
    }

    /**
     * @return Collection<string, SoftwareDependency>
     */
    private function dependenciesFor(Version $version): Collection
    {
        $dependencies = SoftwareDependency::query()
            ->with(['dependsOnSoftware:id,name', 'minVersion:id,version_number', 'maxVersion:id,version_number'])
            ->where('software_id', $version->software_id)
            ->where(function ($query) use ($version): void {
                $query->whereNull('applies_to_version_id')
                    ->orWhere('applies_to_version_id', $version->id);
            })
            ->orderByRaw('applies_to_version_id is null desc')
            ->get();

        return $dependencies->keyBy(fn (SoftwareDependency $dependency): string => $this->dependencyKey($dependency));
    }

    /**
     * @param  Collection<string, SoftwareDependency>  $dependencies
     * @return array<string, mixed>
     */
    private function versionPayload(Version $version, Collection $dependencies): array
    {
        return [
            'id' => $version->id,
            'version' => $version->version_number,
            'release_date' => $version->release_date?->toDateString(),
            'support_status' => $version->support_status?->value,
            'notes' => $version->textContents->map(fn ($content): array => [
                'language' => $content->language?->value,
                'title' => $content->title,
                'content' => $content->content,
            ])->values(),
            'attachments' => $version->fileAttachments->map(fn ($attachment): array => [
                'filename' => $attachment->filename,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
            ])->values(),
            'advisories' => $version->vulnerabilities
                ->reject(fn ($vulnerability): bool => $vulnerability->status?->value === 'false_positive')
                ->map(fn ($vulnerability): array => [
                    'cve_id' => $vulnerability->cve_id,
                    'severity' => $vulnerability->severity?->value,
                    'status' => $vulnerability->status?->value,
                    'cvss_score' => $vulnerability->cvss_score,
                ])->values(),
            'dependencies' => $dependencies->map(fn (SoftwareDependency $dependency): array => $this->dependencyPayload($dependency))->values(),
        ];
    }

    /**
     * @param  Collection<string, SoftwareDependency>  $left
     * @param  Collection<string, SoftwareDependency>  $right
     * @return array<int, array<string, mixed>>
     */
    private function dependencyChanges(Collection $left, Collection $right): array
    {
        return $left->keys()
            ->merge($right->keys())
            ->unique()
            ->map(function (string $key) use ($left, $right): array {
                $before = $left->get($key);
                $after = $right->get($key);
                $beforePayload = $before ? $this->dependencyPayload($before) : null;
                $afterPayload = $after ? $this->dependencyPayload($after) : null;

                return [
                    'status' => $before === null ? 'added' : ($after === null ? 'removed' : ($beforePayload === $afterPayload ? 'unchanged' : 'changed')),
                    'before' => $beforePayload,
                    'after' => $afterPayload,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function dependencyPayload(SoftwareDependency $dependency): array
    {
        return [
            'software_id' => $dependency->depends_on_software_id,
            'software' => $dependency->dependsOnSoftware?->name,
            'type' => $dependency->dependency_type,
            'min_version' => $dependency->minVersion?->version_number,
            'max_version' => $dependency->maxVersion?->version_number,
        ];
    }

    private function dependencyKey(SoftwareDependency $dependency): string
    {
        return $dependency->depends_on_software_id.':'.$dependency->dependency_type;
    }
}
