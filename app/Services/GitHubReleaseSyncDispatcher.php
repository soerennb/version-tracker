<?php

namespace App\Services;

use App\Enums\SourceSyncStatus;
use App\Jobs\SyncGithubReleases;
use App\Models\Software;
use App\Models\SourceSyncRun;
use Illuminate\Support\Facades\DB;

class GitHubReleaseSyncDispatcher
{
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
