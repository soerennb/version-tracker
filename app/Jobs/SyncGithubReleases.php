<?php

namespace App\Jobs;

use App\Enums\SourceSyncStatus;
use App\Models\Software;
use App\Models\SourceSyncRun;
use App\Services\GitHubReleaseClient;
use App\Services\GitHubReleaseImportService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncGithubReleases implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 86400;

    public function __construct(
        public int $softwareId,
        public int $syncRunId,
        public bool $dryRun = false,
    ) {
        $this->onQueue('imports');
    }

    public function uniqueId(): string
    {
        return 'github-release-sync:'.$this->softwareId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(
        GitHubReleaseClient $client,
        GitHubReleaseImportService $importService,
    ): void {
        $software = Software::query()->findOrFail($this->softwareId);
        $syncRun = SourceSyncRun::query()->findOrFail($this->syncRunId);

        $syncRun->update([
            'status' => SourceSyncStatus::RUNNING,
            'started_at' => now(),
            'finished_at' => null,
            'error_message' => null,
        ]);

        try {
            if (! $client->repositoryPath($software->github_repo_url)) {
                throw new \RuntimeException('The configured GitHub repository URL is invalid.');
            }

            $result = $importService->import(
                $software,
                $client->releasesAndTags($software),
                $this->dryRun,
            );

            $syncRun->update([
                'status' => SourceSyncStatus::SUCCEEDED,
                'finished_at' => now(),
                'created_count' => $result['created'],
                'updated_count' => $result['updated'],
                'unchanged_count' => $result['unchanged'],
                'skipped_count' => $result['skipped'],
                'error_count' => count($result['errors']),
                'errors' => $result['errors'],
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($syncRun, $exception);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $syncRun = SourceSyncRun::query()->find($this->syncRunId);

        if ($syncRun) {
            $this->markFailed($syncRun, $exception);
        }
    }

    protected function markFailed(SourceSyncRun $syncRun, Throwable $exception): void
    {
        $syncRun->update([
            'status' => SourceSyncStatus::FAILED,
            'finished_at' => now(),
            'error_count' => max(1, (int) $syncRun->error_count),
            'error_message' => $exception->getMessage(),
            'errors' => [$exception->getMessage()],
        ]);
    }
}
