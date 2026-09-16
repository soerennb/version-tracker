<?php

namespace App\Services;

use App\Enums\SoftwareStatus;
use App\Enums\SourceSyncStatus;
use App\Jobs\SyncGithubReleases;
use App\Models\Software;
use App\Models\SourceSyncRun;
use Illuminate\Support\Facades\DB;

class GitHubReleaseSyncDispatcher
{
    /**
     * @return array{total: int, queued: int, skipped: int}
     */
    public function dispatchActiveRepositories(bool $dryRun = false): array
    {
        $software = Software::query()
            ->where('status', SoftwareStatus::ACTIVE->value)
            ->whereNotNull('github_repo_url')
            ->get();
        $queued = 0;
        $skipped = 0;

        foreach ($software as $record) {
            if ($this->dispatch($record, $dryRun)) {
                $queued++;

                continue;
            }

            $skipped++;
        }

        return [
            'total' => $software->count(),
            'queued' => $queued,
            'skipped' => $skipped,
        ];
    }

    public function dispatch(Software $software, bool $dryRun = false): ?SourceSyncRun
    {
        return DB::transaction(function () use ($software, $dryRun): ?SourceSyncRun {
            $hasActiveRun = SourceSyncRun::query()
                ->where('software_id', $software->id)
                ->where('provider', 'github')
                ->whereIn('status', [SourceSyncStatus::QUEUED, SourceSyncStatus::RUNNING])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveRun) {
                return null;
            }

            $syncRun = SourceSyncRun::query()->create([
                'software_id' => $software->id,
                'provider' => 'github',
                'status' => SourceSyncStatus::QUEUED,
            ]);

            SyncGithubReleases::dispatch($software->id, $syncRun->id, $dryRun)
                ->afterCommit();

            return $syncRun;
        });
    }
}
