<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\SoftwareStatus;
use App\Enums\SourceSyncStatus;
use App\Enums\VersionStatus;
use App\Jobs\SyncGithubReleases;
use App\Models\Software;
use App\Models\SourceSyncRun;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\VersionSource;
use App\Services\GitHubReleaseClient;
use App\Services\GitHubReleaseImportService;
use App\Services\RuntimeSettings;
use App\Settings\GitHubSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Throwable;

class GitHubReleaseSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_queues_one_run_for_each_active_software_repository(): void
    {
        $this->enableGithubSync();
        Queue::fake();

        $active = Software::factory()->create([
            'status' => SoftwareStatus::ACTIVE,
            'github_repo_url' => 'https://github.com/acme/active',
        ]);
        Software::factory()->create([
            'status' => SoftwareStatus::INACTIVE,
            'github_repo_url' => 'https://github.com/acme/inactive',
        ]);

        $this->artisan('app:sync-github-releases')
            ->assertExitCode(0);

        Queue::assertPushed(SyncGithubReleases::class, fn (SyncGithubReleases $job): bool => $job->softwareId === $active->id);
        $this->assertDatabaseHas('source_sync_runs', [
            'software_id' => $active->id,
            'provider' => 'github',
            'status' => SourceSyncStatus::QUEUED->value,
        ]);
        $this->assertDatabaseCount('source_sync_runs', 1);
    }

    public function test_dispatcher_does_not_queue_a_second_active_run(): void
    {
        $this->enableGithubSync();
        Queue::fake();

        $software = Software::factory()->create([
            'status' => SoftwareStatus::ACTIVE,
            'github_repo_url' => 'https://github.com/acme/core',
        ]);

        $this->artisan('app:sync-github-releases')->assertExitCode(0);
        $this->artisan('app:sync-github-releases')->assertExitCode(0);

        Queue::assertPushed(SyncGithubReleases::class, 1);
        $this->assertDatabaseCount('source_sync_runs', 1);
        $this->assertDatabaseHas('source_sync_runs', [
            'software_id' => $software->id,
            'status' => SourceSyncStatus::QUEUED->value,
        ]);
    }

    public function test_job_imports_releases_and_updates_run_metrics_without_publishing(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->githubResponses());

        $software = Software::factory()->create([
            'status' => SoftwareStatus::ACTIVE,
            'github_repo_url' => 'https://github.com/acme/core',
        ]);
        $syncRun = SourceSyncRun::factory()->for($software)->create([
            'status' => SourceSyncStatus::QUEUED,
            'created_count' => 0,
        ]);

        $job = new SyncGithubReleases($software->id, $syncRun->id);
        $job->handle(app(GitHubReleaseClient::class), app(GitHubReleaseImportService::class));

        $syncRun->refresh();
        $version = Version::query()->where('software_id', $software->id)->firstOrFail();

        $this->assertSame(SourceSyncStatus::SUCCEEDED, $syncRun->status);
        $this->assertSame(1, $syncRun->created_count);
        $this->assertSame(0, $syncRun->error_count);
        $this->assertSame(VersionStatus::DRAFT, $version->status);
        $this->assertSame(ApprovalStatus::PENDING, $version->approval_status);
        $this->assertDatabaseHas('version_sources', [
            'software_id' => $software->id,
            'version_id' => $version->id,
            'provider' => 'github',
            'source_kind' => 'release',
            'external_id' => '1234',
        ]);
    }

    public function test_repeated_import_is_idempotent_and_updates_changed_source_metadata(): void
    {
        $software = Software::factory()->create(['github_repo_url' => 'https://github.com/acme/core']);
        $release = [
            'tag_name' => 'v1.2.0',
            'name' => 'Release 1.2.0',
            'body' => 'Initial notes.',
            'published_at' => '2026-03-01T10:00:00Z',
            'source' => 'github_release',
            'source_kind' => 'release',
            'external_id' => '1234',
            'source_url' => 'https://github.com/acme/core/releases/tag/v1.2.0',
            'source_updated_at' => '2026-03-01T10:00:00Z',
        ];
        $service = app(GitHubReleaseImportService::class);

        $first = $service->import($software, [$release]);
        $second = $service->import($software, [$release]);
        $changed = $service->import($software, [[...$release, 'name' => 'Release 1.2.0 updated']]);

        $this->assertSame(1, $first['created']);
        $this->assertSame(1, $second['unchanged']);
        $this->assertSame(1, $changed['updated']);
        $this->assertSame(1, Version::query()->where('software_id', $software->id)->count());
        $this->assertSame(1, VersionSource::query()->where('software_id', $software->id)->count());
        $this->assertSame('Release 1.2.0 updated', VersionSource::query()->firstOrFail()->name);
    }

    public function test_manually_edited_release_notes_are_preserved_and_reported(): void
    {
        $software = Software::factory()->create(['github_repo_url' => 'https://github.com/acme/core']);
        $version = Version::factory()->for($software)->create([
            'version_number' => '1.2.0',
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
        ]);
        $oldBody = 'Imported notes.';
        TextContent::factory()->for($version)->create([
            'language' => 'en',
            'content' => 'Manually curated notes.',
        ]);
        VersionSource::factory()->for($software)->for($version)->create([
            'source_kind' => 'release',
            'external_id' => '1234',
            'tag_name' => 'v1.2.0',
            'imported_content_hash' => hash('sha256', $oldBody),
            'payload_hash' => hash('sha256', 'old-payload'),
        ]);

        $result = app(GitHubReleaseImportService::class)->import($software, [[
            'tag_name' => 'v1.2.0',
            'name' => 'Release 1.2.0',
            'body' => 'Updated upstream notes.',
            'source' => 'github_release',
            'source_kind' => 'release',
            'external_id' => '1234',
        ]]);

        $this->assertSame(1, $result['updated']);
        $this->assertCount(1, $result['errors']);
        $this->assertDatabaseHas('text_contents', [
            'version_id' => $version->id,
            'content' => 'Manually curated notes.',
        ]);
        $this->assertDatabaseHas('version_sources', [
            'version_id' => $version->id,
            'body' => 'Updated upstream notes.',
        ]);
    }

    public function test_failed_job_marks_the_sync_run_failed(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*api.github.com/repos/acme/core/releases*' => Http::response([], 500),
        ]);

        $software = Software::factory()->create([
            'github_repo_url' => 'https://github.com/acme/core',
        ]);
        $syncRun = SourceSyncRun::factory()->for($software)->create([
            'status' => SourceSyncStatus::QUEUED,
        ]);

        try {
            (new SyncGithubReleases($software->id, $syncRun->id))
                ->handle(app(GitHubReleaseClient::class), app(GitHubReleaseImportService::class));
        } catch (Throwable) {
            // The queue worker will retry the failed job; the run state is asserted below.
        }

        $this->assertDatabaseHas('source_sync_runs', [
            'id' => $syncRun->id,
            'status' => SourceSyncStatus::FAILED->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function githubResponses(): array
    {
        return [
            '*api.github.com/repos/acme/core/releases*' => Http::response([
                [
                    'id' => 1234,
                    'tag_name' => 'v1.2.0',
                    'name' => 'Release 1.2.0',
                    'body' => 'Release notes.',
                    'published_at' => '2026-03-01T10:00:00Z',
                    'updated_at' => '2026-03-01T10:00:00Z',
                    'html_url' => 'https://github.com/acme/core/releases/tag/v1.2.0',
                    'draft' => false,
                    'prerelease' => false,
                ],
            ]),
            '*api.github.com/repos/acme/core/tags*' => Http::response([
                ['name' => 'v1.2.0'],
            ]),
        ];
    }

    private function enableGithubSync(): void
    {
        app(GitHubSettings::class)->fill(['sync_enabled' => true])->save();
        app(RuntimeSettings::class)->forgetPublicRuntimeCache();
    }
}
