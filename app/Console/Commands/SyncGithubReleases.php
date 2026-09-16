<?php

namespace App\Console\Commands;

use App\Enums\SoftwareStatus;
use App\Models\Software;
use App\Services\GitHubReleaseSyncDispatcher;
use App\Services\RuntimeSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-github-releases {--software= : Only sync one software ID} {--dry-run : Fetch and evaluate without changing release data} {--force : Run even when GitHub sync is disabled}')]
#[Description('Queue GitHub release and tag synchronization for active software')]
class SyncGithubReleases extends Command
{
    public function __construct(
        protected GitHubReleaseSyncDispatcher $syncDispatcher,
        protected RuntimeSettings $runtimeSettings,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->runtimeSettings->github()->sync_enabled && ! $this->option('force')) {
            $this->components->warn('GitHub synchronization is disabled. Use --force for a manual run.');

            return self::SUCCESS;
        }

        $software = Software::query()
            ->where('status', SoftwareStatus::ACTIVE->value)
            ->whereNotNull('github_repo_url')
            ->when($this->option('software'), fn ($query) => $query->whereKey((int) $this->option('software')))
            ->get();

        if ($software->isEmpty()) {
            $this->components->info('No active software with a GitHub repository was found.');

            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($software as $record) {
            $syncRun = $this->syncDispatcher->dispatch(
                $record,
                (bool) $this->option('dry-run'),
            );

            if (! $syncRun) {
                $this->components->warn("Skipping {$record->name}; a sync is already active.");

                continue;
            }
            $dispatched++;
        }

        $this->components->info("Queued {$dispatched} GitHub synchronization job(s).");

        return self::SUCCESS;
    }
}
