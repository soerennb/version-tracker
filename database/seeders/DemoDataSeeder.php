<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\ComplianceStatus;
use App\Enums\ExploitabilityStatus;
use App\Enums\Language;
use App\Enums\SoftwareStatus;
use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Environment;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    /**
     * Populate the database with a representative public catalog and internal work queue.
     */
    public function run(): void
    {
        $owner = User::query()->where('email', 'demo@example.com')->firstOrFail();

        $this->environments();

        $aurora = $this->software($owner, [
            'name' => 'Aurora Suite',
            'description' => 'A governed analytics platform with a public release channel and a dependency-aware operations model.',
            'license_type' => 'Proprietary',
            'compliance_status' => ComplianceStatus::COMPLIANT,
            'github_repo_url' => 'https://github.com/example/aurora',
        ]);
        $auroraVersions = $this->releases($aurora, $owner, [
            ['version' => '1.0.0', 'released' => $this->referenceDate()->subMonths(13), 'support' => SupportStatus::EOL, 'eol' => $this->referenceDate()->subMonth()],
            ['version' => '1.1.0', 'released' => $this->referenceDate()->subMonths(10), 'support' => SupportStatus::DEPRECATED, 'eol' => $this->referenceDate()->subDays(20)],
            ['version' => '1.2.0', 'released' => $this->referenceDate()->subMonths(7), 'support' => SupportStatus::MAINTENANCE, 'eol' => $this->referenceDate()->addDays(45), 'lts' => $this->referenceDate()->addMonths(3)],
            ['version' => '1.3.0', 'released' => $this->referenceDate()->subMonths(3), 'support' => SupportStatus::SUPPORTED, 'eol' => $this->referenceDate()->addMonths(9)],
            ['version' => '1.4.0', 'released' => $this->referenceDate()->subWeeks(2), 'support' => SupportStatus::SUPPORTED, 'eol' => $this->referenceDate()->addMonths(12), 'lts' => $this->referenceDate()->addMonths(18)],
        ]);

        $beacon = $this->software($owner, [
            'name' => 'Beacon Gateway',
            'description' => 'A service gateway that connects customer systems to the VersionTracker release channel.',
            'license_type' => 'Apache-2.0',
            'compliance_status' => ComplianceStatus::COMPLIANT,
            'github_repo_url' => 'https://github.com/example/beacon',
        ]);
        $beaconVersions = $this->releases($beacon, $owner, [
            ['version' => '2.8.0', 'released' => $this->referenceDate()->subMonths(14), 'support' => SupportStatus::EOL, 'eol' => $this->referenceDate()->subMonths(2)],
            ['version' => '3.0.0', 'released' => $this->referenceDate()->subMonths(9), 'support' => SupportStatus::MAINTENANCE, 'eol' => $this->referenceDate()->addDays(75)],
            ['version' => '3.1.0', 'released' => $this->referenceDate()->subMonths(4), 'support' => SupportStatus::SUPPORTED, 'eol' => $this->referenceDate()->addMonths(8)],
            ['version' => '3.2.0', 'released' => $this->referenceDate()->subWeeks(5), 'support' => SupportStatus::SUPPORTED, 'eol' => $this->referenceDate()->addMonths(14), 'lts' => $this->referenceDate()->addMonths(20)],
        ]);

        $nimbus = $this->software($owner, [
            'name' => 'Nimbus Console',
            'description' => 'An operations console for teams that need a calm view of releases, support windows, and risk.',
            'license_type' => 'MIT',
            'compliance_status' => ComplianceStatus::UNKNOWN,
        ]);
        $nimbusVersions = $this->releases($nimbus, $owner, [
            ['version' => '0.7.0', 'released' => $this->referenceDate()->subMonths(12), 'support' => SupportStatus::EOL, 'eol' => $this->referenceDate()->subMonths(4)],
            ['version' => '0.8.0', 'released' => $this->referenceDate()->subMonths(6), 'support' => SupportStatus::MAINTENANCE, 'eol' => $this->referenceDate()->addMonths(2)],
            ['version' => '0.9.0', 'released' => $this->referenceDate()->subWeeks(3), 'support' => SupportStatus::MAINTENANCE, 'eol' => $this->referenceDate()->addMonths(5)],
        ]);

        $pending = $this->release($nimbus, $owner, '1.0.0', $this->referenceDate()->addWeeks(2), [
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
        ]);
        $this->note($pending, Language::DE, 'Nimbus Console 1.0.0', 'Die nächste Generation der Operations-Übersicht ist bereit für die interne Prüfung.');

        $this->vulnerability($auroraVersions['1.3.0'], [
            'cve_id' => 'CVE-2026-1001',
            'severity' => VulnerabilitySeverity::HIGH,
            'cvss_score' => 8.2,
            'description' => 'A crafted dashboard export can bypass a permission check for users with limited reporting access.',
            'source' => 'NVD',
            'source_url' => 'https://nvd.nist.gov/vuln/detail/CVE-2026-1001',
            'affected_range' => '>=1.2.0 <1.4.0',
            'fixed_version_id' => $auroraVersions['1.4.0']->id,
            'status' => VulnerabilityStatus::OPEN,
            'exploitability' => ExploitabilityStatus::PROOF_OF_CONCEPT,
            'published_date' => $this->referenceDate()->subWeeks(4),
        ]);
        $this->vulnerability($beaconVersions['3.1.0'], [
            'cve_id' => 'CVE-2026-1002',
            'severity' => VulnerabilitySeverity::CRITICAL,
            'cvss_score' => 9.4,
            'description' => 'A malformed upstream token may be replayed against the gateway before the trust boundary is evaluated.',
            'source' => 'OSV',
            'source_url' => 'https://osv.dev/vulnerability/CVE-2026-1002',
            'affected_range' => '>=3.0.0 <3.2.0',
            'fixed_version_id' => $beaconVersions['3.2.0']->id,
            'status' => VulnerabilityStatus::OPEN,
            'exploitability' => ExploitabilityStatus::ACTIVE,
            'published_date' => $this->referenceDate()->subDays(12),
        ]);
        $this->vulnerability($nimbusVersions['0.8.0'], [
            'cve_id' => 'CVE-2026-1003',
            'severity' => VulnerabilitySeverity::MEDIUM,
            'cvss_score' => 5.4,
            'description' => 'The console accepts an overly broad redirect target in an older invitation flow.',
            'source' => 'GitHub Advisory',
            'source_url' => 'https://github.com/advisories/CVE-2026-1003',
            'affected_range' => '<0.9.0',
            'fixed_version_id' => $nimbusVersions['0.9.0']->id,
            'status' => VulnerabilityStatus::FIXED,
            'exploitability' => ExploitabilityStatus::NO_KNOWN_EXPLOIT,
            'published_date' => $this->referenceDate()->subMonths(5),
        ]);

        $this->attachment($auroraVersions['1.4.0'], 'aurora-1.4.0-release-notes.pdf', 'Aurora Suite 1.4.0 release notes\n\nTimeline and approval workflow improvements.');
        $this->attachment($auroraVersions['1.4.0'], 'aurora-1.4.0-linux.zip', 'Aurora Suite 1.4.0 demo artifact.');
        $this->attachment($beaconVersions['3.2.0'], 'beacon-3.2.0-linux.zip', 'Beacon Gateway 3.2.0 demo artifact.');
        $this->attachment($nimbusVersions['0.9.0'], 'nimbus-0.9.0.tar.gz', 'Nimbus Console 0.9.0 demo artifact.');

        $this->dependency(
            $beacon,
            $aurora,
            $beaconVersions['3.2.0']->id,
            $auroraVersions['1.3.0']->id,
            $auroraVersions['1.4.0']->id,
            'runtime',
        );
        $this->dependency(
            $nimbus,
            $beacon,
            $nimbusVersions['0.9.0']->id,
            $beaconVersions['3.2.0']->id,
            $beaconVersions['3.0.0']->id,
            'runtime',
        );

        $this->refreshSoftware($aurora, $owner);
        $this->refreshSoftware($beacon, $owner);
        $this->refreshSoftware($nimbus, $owner);
    }

    private function environments(): void
    {
        foreach ([
            ['name' => 'Development', 'code' => 'development', 'is_production' => false, 'sort_order' => 10],
            ['name' => 'Acceptance', 'code' => 'acceptance', 'is_production' => false, 'sort_order' => 20],
            ['name' => 'Production', 'code' => 'production', 'is_production' => true, 'sort_order' => 30],
        ] as $environment) {
            Environment::query()->updateOrCreate(
                ['code' => $environment['code']],
                [
                    'name' => $environment['name'],
                    'is_production' => $environment['is_production'],
                    'is_active' => true,
                    'sort_order' => $environment['sort_order'],
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function software(User $owner, array $attributes): Software
    {
        return Software::updateOrCreate(
            ['name' => $attributes['name']],
            [
                ...$attributes,
                'status' => SoftwareStatus::ACTIVE,
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ],
        );
    }

    private function dependency(
        Software $software,
        Software $dependsOnSoftware,
        int $appliesToVersionId,
        ?int $minVersionId,
        ?int $maxVersionId,
        string $dependencyType,
    ): void {
        $dependency = SoftwareDependency::query()->firstOrNew([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependsOnSoftware->id,
            'applies_to_version_id' => $appliesToVersionId,
            'dependency_type' => $dependencyType,
        ]);
        $dependency->fill([
            'min_version_id' => $minVersionId,
            'max_version_id' => $maxVersionId,
            'dependency_type' => $dependencyType,
        ]);
        $dependency->scope_key = SoftwareDependency::makeScopeKey(
            (int) $software->id,
            (int) $dependsOnSoftware->id,
            $appliesToVersionId,
            $dependencyType,
        );
        $dependency->save();
    }

    /**
     * @param  array<int, array<string, mixed>>  $releases
     * @return array<string, Version>
     */
    private function releases(Software $software, User $owner, array $releases): array
    {
        $versions = [];

        foreach ($releases as $release) {
            $version = $this->release($software, $owner, $release['version'], $release['released'], [
                'support_status' => $release['support'] ?? null,
                'eol_date' => $release['eol'] ?? null,
                'lts_date' => $release['lts'] ?? null,
            ]);

            $this->note($version, Language::DE, "{$software->name} {$release['version']}", $this->releaseCopy($software, $release['version'], Language::DE));
            $this->note($version, Language::EN, "{$software->name} {$release['version']}", $this->releaseCopy($software, $release['version'], Language::EN));
            $versions[$release['version']] = $version;
        }

        return $versions;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function release(Software $software, User $owner, string $versionNumber, Carbon $releaseDate, array $attributes = []): Version
    {
        return $software->versions()->updateOrCreate(
            ['version_number' => $versionNumber],
            [
                'created_by' => $owner->id,
                'release_date' => $releaseDate,
                'status' => VersionStatus::PUBLISHED,
                'approval_status' => ApprovalStatus::APPROVED,
                ...$attributes,
            ],
        );
    }

    private function note(Version $version, Language $language, string $title, string $content): void
    {
        TextContent::updateOrCreate(
            ['version_id' => $version->id, 'language' => $language->value],
            ['title' => $title, 'content' => $content],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function vulnerability(Version $version, array $attributes): void
    {
        Vulnerability::updateOrCreate(
            [
                'cve_id' => $attributes['cve_id'],
                'affected_version_id' => $version->id,
            ],
            [
                ...$attributes,
            ],
        );
    }

    private function attachment(Version $version, string $filename, string $contents): void
    {
        $diskName = config('filesystems.default', 'local');
        $path = "attachments/{$version->id}/{$filename}";
        $disk = Storage::disk($diskName);
        $disk->put($path, $contents);

        FileAttachment::updateOrCreate(
            ['version_id' => $version->id, 'filename' => $filename],
            [
                'file_path' => $path,
                'artifact_type' => str_ends_with($filename, '.zip') ? 'installer' : 'release',
                'platform' => str_contains($filename, 'linux') || str_contains($filename, '.tar.gz') ? 'linux' : null,
                'architecture' => str_contains($filename, 'linux') || str_contains($filename, '.tar.gz') ? 'x86_64' : null,
                'mime_type' => str_ends_with($filename, '.zip') ? 'application/zip' : 'text/plain',
                'size' => $disk->size($path),
                'checksum' => hash('sha256', $contents),
                'checksum_algorithm' => 'sha256',
                'verification_status' => 'verified',
                'is_public' => true,
            ],
        );
    }

    private function refreshSoftware(Software $software, User $owner): void
    {
        $latest = $software->versions()
            ->where('status', VersionStatus::PUBLISHED)
            ->latest('release_date')
            ->first();

        $software->update([
            'current_version' => $latest?->version_number,
            'last_release_date' => $latest?->release_date,
            'updated_by' => $owner->id,
        ]);
    }

    private function releaseCopy(Software $software, string $version, Language $language): string
    {
        return match ($software->name) {
            'Aurora Suite' => $language->isGerman()
                ? "Neue Governance-Ansichten, Lifecycle-Signale und ein klarerer Freigabeprozess für Aurora {$version}."
                : "New governance views, lifecycle signals, and a clearer approval flow for Aurora {$version}.",
            'Beacon Gateway' => $language->isGerman()
                ? "Stabilere Token-Prüfung, bessere Gateway-Metriken und aktualisierte Integrationshinweise für Beacon {$version}."
                : "Stronger token validation, improved gateway metrics, and updated integration guidance for Beacon {$version}.",
            default => $language->isGerman()
                ? "Verbesserte Betriebsübersicht, schnellere Suche und aktualisierte Sicherheitsinformationen für {$software->name} {$version}."
                : "A calmer operations view, faster search, and refreshed security information for {$software->name} {$version}.",
        };
    }

    private function referenceDate(): Carbon
    {
        return Carbon::parse((string) config('installation.demo_reference_date', '2026-01-01'))->startOfDay();
    }
}
