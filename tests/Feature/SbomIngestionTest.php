<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\ComponentFinding;
use App\Models\FileAttachment;
use App\Models\ReleaseException;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\ReleaseReadinessService;
use App\Services\RuntimeSettings;
use App\Settings\GovernanceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SbomIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cyclonedx_sbom_is_parsed_and_retries_are_idempotent(): void
    {
        $user = User::factory()->editor()->create();
        Sanctum::actingAs($user);
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        $document = json_encode([
            'bomFormat' => 'CycloneDX',
            'specVersion' => '1.6',
            'serialNumber' => 'urn:uuid:demo',
            'components' => [[
                'bom-ref' => 'pkg:npm/acme/core@1.2.3',
                'type' => 'library',
                'group' => '@acme',
                'name' => 'core',
                'version' => '1.2.3',
                'purl' => 'pkg:npm/%40acme/core@1.2.3',
                'licenses' => [['license' => ['id' => 'MIT']]],
            ]],
            'vulnerabilities' => [[
                'id' => 'CVE-2026-1000',
                'ratings' => [['severity' => 'high', 'score' => 8.1]],
                'affects' => [['ref' => 'pkg:npm/acme/core@1.2.3']],
            ]],
        ], JSON_THROW_ON_ERROR);

        $response = $this->withHeader('Idempotency-Key', 'ci-run-1')->postJson('/api/versions/'.$version->id.'/sboms', [
            'document' => $document,
            'source' => 'ci',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.format', 'cyclonedx')
            ->assertJsonPath('data.component_count', 1)
            ->assertJsonPath('data.finding_count', 1)
            ->assertJsonPath('data.components.0.name', 'core')
            ->assertJsonPath('data.findings.0.external_id', 'CVE-2026-1000');
        $this->assertDatabaseHas('sbom_documents', [
            'version_id' => $version->id,
            'source' => 'ci',
            'idempotency_key' => 'ci-run-1',
        ]);
        $this->assertDatabaseHas('component_findings', [
            'external_id' => 'CVE-2026-1000',
            'severity' => VulnerabilitySeverity::HIGH->value,
        ]);

        $this->withHeader('Idempotency-Key', 'ci-run-1')
            ->postJson('/api/versions/'.$version->id.'/sboms', ['document' => $document])
            ->assertOk()
            ->assertJsonPath('data.id', $response->json('data.id'));
        $this->assertDatabaseCount('sbom_documents', 1);
    }

    public function test_spdx_documents_are_supported_and_invalid_documents_are_rejected(): void
    {
        $user = User::factory()->editor()->create();
        Sanctum::actingAs($user);
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        $spdx = json_encode([
            'spdxVersion' => 'SPDX-2.3',
            'documentNamespace' => 'https://example.test/sbom/1',
            'packages' => [[
                'SPDXID' => 'SPDXRef-Package',
                'name' => 'openssl',
                'versionInfo' => '3.0.0',
                'licenseDeclared' => 'Apache-2.0',
                'externalRefs' => [[
                    'referenceType' => 'purl',
                    'referenceLocator' => 'pkg:generic/openssl@3.0.0',
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->postJson('/api/versions/'.$version->id.'/sboms', ['document' => $spdx])
            ->assertCreated()
            ->assertJsonPath('data.format', 'spdx')
            ->assertJsonPath('data.components.0.purl', 'pkg:generic/openssl@3.0.0');

        $this->postJson('/api/versions/'.$version->id.'/sboms', ['document' => '{invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document');
    }

    public function test_readiness_reports_sbom_risk_and_expiring_exceptions(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'support_status' => 'supported',
        ]);
        TextContent::factory()->for($version)->create(['language' => Language::DE]);
        TextContent::factory()->for($version)->create(['language' => Language::EN]);
        FileAttachment::factory()->for($version)->create();
        Vulnerability::factory()->for($version, 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::CRITICAL,
            'status' => VulnerabilityStatus::OPEN,
        ]);

        $settings = app(GovernanceSettings::class);
        $settings->fill(['require_sbom' => true, 'sbom_max_age_days' => 30])->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();

        $this->getJson('/api/versions/'.$version->id.'/readiness')
            ->assertOk()
            ->assertJsonPath('readiness.is_ready', false)
            ->assertJsonPath('readiness.sbom.document_id', null);

        ReleaseException::factory()->create([
            'version_id' => $version->id,
            'check_code' => 'blocking_vulnerabilities',
            'expires_at' => now()->addDay(),
        ]);
        $this->assertFalse(app(ReleaseReadinessService::class)->evaluate($version->fresh())['is_ready']);

        ReleaseException::factory()->create([
            'version_id' => $version->id,
            'check_code' => 'missing_sbom',
            'expires_at' => now()->addDay(),
        ]);
        $readiness = app(ReleaseReadinessService::class)->evaluate($version->fresh());
        $codes = collect($readiness['blockers'])->pluck('code')->all();
        $this->assertNotContains('blocking_vulnerabilities', $codes);
        $this->assertNotContains('missing_sbom', $codes);
    }

    public function test_drafts_and_public_pull_contract_remain_unchanged(): void
    {
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        $this->getJson('/api/public/releases/'.$version->id)->assertNotFound();

        $version->forceFill(['status' => VersionStatus::PUBLISHED])->save();
        $this->getJson('/api/public/releases/'.$version->id)->assertOk();
    }

    public function test_admin_can_enrich_an_sbom_from_osv_without_exposing_the_raw_document(): void
    {
        Http::fake([
            config('services.osv.url') => Http::response([
                'results' => [[
                    'vulns' => [[
                        'id' => 'GHSA-demo-1234',
                        'summary' => 'A test advisory',
                        'aliases' => ['CVE-2026-1234'],
                        'database_specific' => ['severity' => 'high'],
                        'references' => [['url' => 'https://osv.dev/vulnerability/GHSA-demo-1234']],
                        'affected' => [['ranges' => [['events' => [['introduced' => '0'], ['fixed' => '1.2.4']]]]]],
                    ]],
                ]],
            ]),
        ]);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        $payload = json_encode([
            'bomFormat' => 'CycloneDX',
            'specVersion' => '1.6',
            'components' => [[
                'bom-ref' => 'pkg:composer/acme/core@1.2.3',
                'name' => 'acme/core',
                'version' => '1.2.3',
                'purl' => 'pkg:composer/acme/core@1.2.3',
            ]],
        ], JSON_THROW_ON_ERROR);

        $document = $this->postJson('/api/versions/'.$version->id.'/sboms', ['document' => $payload])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/sboms/'.$document.'/enrich')
            ->assertOk()
            ->assertJsonPath('created', 1);
        $this->assertDatabaseHas('component_findings', [
            'sbom_document_id' => $document,
            'external_id' => 'GHSA-demo-1234',
            'fixed_version' => '1.2.4',
        ]);
        $this->getJson('/api/sboms/'.$document)
            ->assertOk()
            ->assertJsonMissingPath('data.payload');
    }

    public function test_readiness_uses_the_latest_processed_sbom_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $version = Version::factory()->create([
            'status' => VersionStatus::DRAFT,
            'support_status' => 'supported',
        ]);
        TextContent::factory()->for($version)->create(['language' => Language::DE]);
        TextContent::factory()->for($version)->create(['language' => Language::EN]);
        FileAttachment::factory()->for($version)->create();

        $vulnerablePayload = json_encode([
            'bomFormat' => 'CycloneDX',
            'specVersion' => '1.6',
            'components' => [[
                'bom-ref' => 'pkg:composer/acme/core@1.2.3',
                'name' => 'acme/core',
                'version' => '1.2.3',
                'purl' => 'pkg:composer/acme/core@1.2.3',
            ]],
            'vulnerabilities' => [[
                'id' => 'CVE-2026-9999',
                'ratings' => [['severity' => 'critical', 'score' => 9.8]],
            ]],
        ], JSON_THROW_ON_ERROR);
        $cleanPayload = json_encode([
            'bomFormat' => 'CycloneDX',
            'specVersion' => '1.6',
            'components' => [[
                'bom-ref' => 'pkg:composer/acme/core@1.2.4',
                'name' => 'acme/core',
                'version' => '1.2.4',
                'purl' => 'pkg:composer/acme/core@1.2.4',
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->postJson('/api/versions/'.$version->id.'/sboms', ['document' => $vulnerablePayload])
            ->assertCreated();
        $this->assertSame(1, app(ReleaseReadinessService::class)->evaluate($version->fresh())['risks']['total']);

        $this->postJson('/api/versions/'.$version->id.'/sboms', ['document' => $cleanPayload])
            ->assertCreated();
        $this->assertSame(0, app(ReleaseReadinessService::class)->evaluate($version->fresh())['risks']['total']);
    }

    public function test_optional_epss_and_kev_enrichment_updates_component_risk(): void
    {
        config(['services.risk_intelligence.enabled' => true]);
        Http::fake([
            config('services.osv.url') => Http::response(['results' => [[]]]),
            config('services.risk_intelligence.epss_url').'*' => Http::response([
                'data' => [['cve' => 'CVE-2026-7777', 'epss' => '0.42']],
            ]),
            config('services.risk_intelligence.kev_url') => Http::response([
                'vulnerabilities' => [['cveID' => 'CVE-2026-7777']],
            ]),
        ]);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        $payload = json_encode([
            'bomFormat' => 'CycloneDX',
            'specVersion' => '1.6',
            'components' => [[
                'bom-ref' => 'pkg:npm/acme/core@1.0.0',
                'name' => 'acme/core',
                'version' => '1.0.0',
                'purl' => 'pkg:npm/acme/core@1.0.0',
            ]],
            'vulnerabilities' => [[
                'id' => 'CVE-2026-7777',
                'ratings' => [['severity' => 'high', 'score' => 8.0]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $document = $this->postJson('/api/versions/'.$version->id.'/sboms', ['document' => $payload])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/sboms/'.$document.'/enrich')
            ->assertOk()
            ->assertJsonPath('risk_updated', 1);
        $this->assertDatabaseHas('component_findings', [
            'sbom_document_id' => $document,
            'external_id' => 'CVE-2026-7777',
            'is_kev' => 1,
        ]);

        $finding = ComponentFinding::query()->where('sbom_document_id', $document)->firstOrFail();
        $this->assertSame('0.4200', $finding->epss_score);
        $this->assertSame('73.50', $finding->risk_score);
    }
}
