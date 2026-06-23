<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\Language;
use App\Enums\VersionStatus;
use App\Models\Software;
use App\Models\Version;
use App\Services\GitHubReleaseClient;
use App\Services\GitHubReleaseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubReleaseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_github_client_fetches_releases_and_tags_from_repo_url(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.github.com/repos/acme/core/releases' => Http::response([
                [
                    'tag_name' => 'v1.2.0',
                    'name' => 'Release 1.2.0',
                    'body' => 'Release notes',
                    'published_at' => '2026-03-01T10:00:00Z',
                    'html_url' => 'https://github.com/acme/core/releases/tag/v1.2.0',
                ],
            ]),
            'api.github.com/repos/acme/core/tags' => Http::response([
                ['name' => 'v1.2.0'],
                ['name' => 'v1.1.0'],
            ]),
        ]);

        $software = Software::factory()->create([
            'github_repo_url' => 'https://github.com/acme/core.git',
        ]);

        $releases = app(GitHubReleaseClient::class)->releasesAndTags($software);

        $this->assertCount(2, $releases);
        $this->assertSame('v1.2.0', $releases[0]['tag_name']);
        $this->assertSame('github_release', $releases[0]['source']);
        $this->assertSame('v1.1.0', $releases[1]['tag_name']);
        $this->assertSame('github_tag', $releases[1]['source']);
    }

    public function test_release_import_creates_draft_versions_with_release_notes(): void
    {
        $software = Software::factory()->create(['name' => 'Core API']);

        $result = app(GitHubReleaseImportService::class)->import($software, [
            [
                'tag_name' => 'v1.2.0',
                'name' => 'Release 1.2.0',
                'body' => 'Changes from GitHub.',
                'published_at' => '2026-03-01T10:00:00Z',
            ],
        ]);

        $version = Version::query()->firstOrFail();

        $this->assertSame(1, $result['created']);
        $this->assertSame('1.2.0', $version->version_number);
        $this->assertSame(VersionStatus::DRAFT, $version->status);
        $this->assertSame(ApprovalStatus::PENDING, $version->approval_status);
        $this->assertSame('2026-03-01', $version->release_date->toDateString());
        $this->assertDatabaseHas('text_contents', [
            'version_id' => $version->id,
            'title' => 'Release 1.2.0',
            'content' => 'Changes from GitHub.',
            'language' => Language::EN->value,
        ]);
    }

    public function test_release_import_dry_run_and_skip_existing_versions(): void
    {
        $software = Software::factory()->create(['name' => 'Core API']);
        Version::factory()->for($software)->create(['version_number' => '1.2.0']);

        $result = app(GitHubReleaseImportService::class)->import($software, [
            ['tag_name' => 'v1.2.0'],
            ['tag_name' => 'not-semver'],
            ['tag_name' => 'v1.3.0'],
        ], dryRun: true);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['suggested']);
        $this->assertSame(2, $result['skipped']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame(1, Version::query()->count());
    }
}
