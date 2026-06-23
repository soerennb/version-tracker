<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\Language;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\SoftwareDependency;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Support\Collection;

class AdminWorkQueueService
{
    public function __construct(private readonly DependencyHealthService $dependencyHealthService) {}

    /**
     * @return array<string, Collection<int, mixed>>
     */
    public function queues(int $limit = 8): array
    {
        $pendingQuery = Version::query()
            ->with('software:id,name')
            ->where('approval_status', ApprovalStatus::PENDING);

        return [
            'due_reviews' => (clone $pendingQuery)
                ->where(fn ($query) => $query->whereNull('release_date')->orWhereDate('release_date', '<=', today()))
                ->oldest('release_date')
                ->limit($limit)
                ->get(),
            'pending_approvals' => (clone $pendingQuery)
                ->latest('updated_at')
                ->limit($limit)
                ->get(),
            'security_blockers' => Vulnerability::query()
                ->with('affectedVersion.software:id,name')
                ->where('status', VulnerabilityStatus::OPEN)
                ->whereIn('severity', [VulnerabilitySeverity::CRITICAL, VulnerabilitySeverity::HIGH])
                ->orderByDesc('cvss_score')
                ->limit($limit)
                ->get(),
            'eol_soon' => Version::query()
                ->with('software:id,name')
                ->where('status', VersionStatus::PUBLISHED)
                ->whereBetween('eol_date', [today(), today()->addDays(90)])
                ->orderBy('eol_date')
                ->limit($limit)
                ->get(),
            'incomplete_notes' => $this->incompleteNotes($limit),
            'broken_dependencies' => $this->brokenDependencies($limit),
        ];
    }

    /**
     * @return Collection<int, Version>
     */
    private function incompleteNotes(int $limit): Collection
    {
        return Version::query()
            ->with(['software:id,name', 'textContents:id,version_id,language'])
            ->where('status', VersionStatus::DRAFT)
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->filter(function (Version $version): bool {
                $languages = $version->textContents->pluck('language')
                    ->map(fn (Language|string $language): string => $language instanceof Language ? $language->value : $language);

                return $languages->intersect(Language::values())->count() < count(Language::values());
            })
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, SoftwareDependency>
     */
    private function brokenDependencies(int $limit): Collection
    {
        return SoftwareDependency::query()
            ->with([
                'software:id,name',
                'dependsOnSoftware.versions.vulnerabilities',
                'minVersion',
                'maxVersion',
            ])
            ->limit(100)
            ->get()
            ->filter(fn (SoftwareDependency $dependency): bool => $this->dependencyHealthService->evaluate($dependency)['status'] === 'broken')
            ->take($limit)
            ->values();
    }
}
