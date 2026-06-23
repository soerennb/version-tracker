<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Version;
use App\Models\VersionReview;
use Illuminate\Support\Collection;

class ApprovalCockpitService
{
    public function __construct(
        protected ReleaseReadinessService $releaseReadinessService,
        protected ImpactAnalysisService $impactAnalysisService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Version $version): array
    {
        $version->loadMissing([
            'fileAttachments',
            'software',
            'software.dependenciesOutgoing.dependsOnSoftware',
            'software.dependenciesOutgoing.minVersion',
            'software.dependenciesOutgoing.maxVersion',
            'textContents',
            'reviews.user',
            'vulnerabilities.fixedVersion',
        ]);

        return [
            'version' => $version,
            'readiness' => $this->releaseReadinessService->evaluate($version),
            'release_notes' => $version->textContents->sortBy('language.value')->values(),
            'attachments' => $version->fileAttachments->sortBy('filename')->values(),
            'vulnerabilities' => $version->vulnerabilities->sortByDesc('cvss_score')->values(),
            'dependencies' => $version->software?->dependenciesOutgoing->values() ?? collect(),
            'impact' => $this->impactAnalysisService->forVersion($version)['affected_software'] ?? [],
            'reviews' => $version->reviews->sortByDesc('created_at')->values(),
            'audit_changes' => $this->auditChanges($version),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function auditChanges(Version $version): Collection
    {
        $reviewIds = $version->reviews->pluck('id')->all();

        return AuditLog::query()
            ->with('user')
            ->where(function ($query) use ($reviewIds, $version): void {
                $query->where(function ($query) use ($version): void {
                    $query->where('model_type', Version::class)
                        ->where('model_id', $version->id);
                });

                if ($reviewIds !== []) {
                    $query->orWhere(function ($query) use ($reviewIds): void {
                        $query->where('model_type', VersionReview::class)
                            ->whereIn('model_id', $reviewIds);
                    });
                }
            })
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (AuditLog $auditLog): array => [
                'created_at' => $auditLog->created_at,
                'action' => $auditLog->action,
                'user' => $auditLog->user?->name,
                'changes' => $auditLog->getChangedFields(),
            ]);
    }
}
