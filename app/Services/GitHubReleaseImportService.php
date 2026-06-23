<?php

namespace App\Services;

use App\Enums\Language;
use App\Helpers\VersionHelper;
use App\Models\Software;
use App\Models\TextContent;
use Carbon\Carbon;

class GitHubReleaseImportService
{
    public function __construct(
        protected VersionService $versionService,
    ) {}

    /**
     * @param  iterable<int, array<string, mixed>>  $releases
     * @return array{created:int,suggested:int,skipped:int,errors:array<int, string>}
     */
    public function import(Software $software, iterable $releases, bool $dryRun = false): array
    {
        $result = [
            'created' => 0,
            'suggested' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($releases as $index => $release) {
            $versionNumber = $this->versionNumber($release['tag_name'] ?? null);

            if (! $versionNumber || ! VersionHelper::isValidSemver($versionNumber)) {
                $result['skipped']++;
                $result['errors'][] = 'Release '.$index.': invalid SemVer tag.';

                continue;
            }

            if ($software->versions()->where('version_number', $versionNumber)->exists()) {
                $result['skipped']++;

                continue;
            }

            if ($dryRun) {
                $result['suggested']++;

                continue;
            }

            $version = $this->versionService->create([
                'software_id' => $software->id,
                'version_number' => $versionNumber,
                'release_date' => $this->releaseDate($release['published_at'] ?? null),
            ]);

            TextContent::create([
                'version_id' => $version->id,
                'title' => (string) ($release['name'] ?? $release['tag_name'] ?? $versionNumber),
                'content' => filled($release['body'] ?? null)
                    ? (string) $release['body']
                    : 'Imported from GitHub '.$versionNumber.'.',
                'language' => Language::EN,
            ]);

            $result['created']++;
        }

        return $result;
    }

    /**
     * @return array{created:int,suggested:int,skipped:int,errors:array<int, string>}
     */
    public function importFromGitHub(Software $software, bool $dryRun = false): array
    {
        return $this->import($software, app(GitHubReleaseClient::class)->releasesAndTags($software), $dryRun);
    }

    protected function versionNumber(?string $tagName): ?string
    {
        if (! $tagName) {
            return null;
        }

        return ltrim($tagName, 'vV');
    }

    protected function releaseDate(?string $publishedAt): string
    {
        if (! $publishedAt) {
            return now()->toDateString();
        }

        return Carbon::parse($publishedAt)->toDateString();
    }
}
