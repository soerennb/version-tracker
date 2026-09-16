<?php

namespace Tests\Feature;

use App\Enums\SoftwareStatus;
use App\Enums\SourceSyncStatus;
use App\Enums\VersionSourceKind;
use App\Filament\Pages\AnalyticsDashboard;
use App\Filament\Widgets\GithubSyncStatus;
use App\Filament\Widgets\LatestGithubMessages;
use App\Jobs\SyncGithubReleases;
use App\Models\Software;
use App\Models\SourceSyncRun;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionSource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class GithubMessagesWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_latest_messages_widget_shows_ten_releases_and_tags_in_source_order(): void
    {
        $admin = User::factory()->admin()->create();
        $software = Software::factory()->create(['github_repo_url' => 'https://github.com/acme/core']);
        $version = Version::factory()->for($software)->create();
        $sources = collect();

        foreach (range(1, 12) as $index) {
            $sources->push(VersionSource::factory()->create([
                'software_id' => $software->id,
                'version_id' => $version->id,
                'source_kind' => $index % 2 === 0 ? VersionSourceKind::TAG : VersionSourceKind::RELEASE,
                'external_id' => (string) $index,
                'tag_name' => 'v1.'.$index.'.0',
                'name' => 'GitHub message '.$index,
                'source_updated_at' => now()->subMinutes($index),
                'last_seen_at' => now()->subMinutes($index),
            ]));
        }

        $this->actingAs($admin);

        Livewire::test(LatestGithubMessages::class)
            ->assertOk()
            ->assertCanSeeTableRecords($sources->take(10))
            ->assertCanNotSeeTableRecords($sources->skip(10))
            ->assertSee('GitHub message 1');
    }

    public function test_latest_messages_widget_reads_imported_data_without_calling_github(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(LatestGithubMessages::class)->assertOk();
    }

    public function test_sync_status_widget_reports_repository_run_and_failure_counts(): void
    {
        $admin = User::factory()->admin()->create();
        Software::factory()->create(['status' => SoftwareStatus::ACTIVE, 'github_repo_url' => 'https://github.com/acme/one']);
        Software::factory()->create(['status' => SoftwareStatus::ACTIVE, 'github_repo_url' => 'https://github.com/acme/two']);
        Software::factory()->create(['status' => SoftwareStatus::INACTIVE, 'github_repo_url' => 'https://github.com/acme/old']);
        SourceSyncRun::factory()->create(['status' => SourceSyncStatus::RUNNING]);
        SourceSyncRun::factory()->create([
            'status' => SourceSyncStatus::FAILED,
            'created_at' => now()->subHours(2),
        ]);
        $this->actingAs($admin);

        Livewire::test(GithubSyncStatus::class)
            ->assertOk()
            ->assertSee(__('filament.widgets.github_sync.heading'))
            ->assertSee(__('filament.widgets.github_sync.active_repositories'))
            ->assertSee(__('filament.widgets.github_sync.active_runs'))
            ->assertSee(__('filament.widgets.github_sync.failed_runs'));
    }

    public function test_dashboard_action_queues_all_active_github_repositories(): void
    {
        Queue::fake();
        $admin = User::factory()->admin()->create();
        $active = Software::factory()->create([
            'status' => SoftwareStatus::ACTIVE,
            'github_repo_url' => 'https://github.com/acme/active',
        ]);
        Software::factory()->create([
            'status' => SoftwareStatus::INACTIVE,
            'github_repo_url' => 'https://github.com/acme/inactive',
        ]);
        $this->actingAs($admin);

        Livewire::test(AnalyticsDashboard::class)
            ->callAction('syncAllGithubRepositories')
            ->assertNotified();

        Queue::assertPushed(SyncGithubReleases::class, fn (SyncGithubReleases $job): bool => $job->softwareId === $active->id);
        $this->assertDatabaseHas('source_sync_runs', [
            'software_id' => $active->id,
            'provider' => 'github',
            'status' => SourceSyncStatus::QUEUED->value,
        ]);
    }
}
