<?php

namespace App\Services;

use App\Enums\Language;
use App\Enums\VersionSourceKind;
use App\Helpers\VersionHelper;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\VersionSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class GitHubReleaseImportService
{
    public function __construct(
        protected VersionService $versionService,
    ) {}

    /**
     * @param  iterable<int, array<string, mixed>>  $releases
     * @return array{created:int,updated:int,unchanged:int,suggested:int,skipped:int,errors:array<int, string>}
     */
    public function import(Software $software, iterable $releases, bool $dryRun = false): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'suggested' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($releases as $index => $release) {
            if (! is_array($release)) {
                $result['skipped']++;
                $result['errors'][] = 'Release '.$index.': invalid source payload.';

                continue;
            }

            $versionNumber = $this->versionNumber($release['tag_name'] ?? null);

            if (! $versionNumber || ! VersionHelper::isValidSemver($versionNumber)) {
                $result['skipped']++;
                $result['errors'][] = 'Release '.$index.': invalid SemVer tag.';

                continue;
            }

            $version = $software->versions()
                ->where('version_number', $versionNumber)
                ->first();
            $hasSourceIdentity = $this->hasSourceIdentity($release);

            if ($version && ! $hasSourceIdentity) {
                $result['skipped']++;

                continue;
            }

            if ($dryRun) {
                $versionSource = $version
                    ? $this->findSource($software, $release)
                    : null;

                if ($versionSource && $versionSource->payload_hash === $this->payloadHash($release)) {
                    $result['unchanged']++;
                } else {
                    $result['suggested']++;
                }

                continue;
            }

            try {
                $outcome = DB::transaction(fn (): array => $this->syncRelease($software, $version, $versionNumber, $release));
                $result[$outcome['status']]++;

                if ($outcome['warning'] !== null) {
                    $result['errors'][] = $outcome['warning'];
                }
            } catch (Throwable $exception) {
                $result['skipped']++;
                $result['errors'][] = 'Release '.$index.': '.$exception->getMessage();
            }
        }

        return $result;
    }

    /**
     * @return array{created:int,updated:int,unchanged:int,suggested:int,skipped:int,errors:array<int, string>}
     */
    public function importFromGitHub(Software $software, bool $dryRun = false): array
    {
        return $this->import($software, app(GitHubReleaseClient::class)->releasesAndTags($software), $dryRun);
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array{status:'created'|'updated'|'unchanged',warning:?string}
     */
    protected function syncRelease(Software $software, ?Version $version, string $versionNumber, array $release): array
    {
        $sourceKind = $this->sourceKind($release);
        $sourceKey = [
            'software_id' => $software->id,
            'provider' => 'github',
            'source_kind' => $sourceKind->value,
            'external_id' => $this->externalId($release),
        ];
        $payloadHash = $this->payloadHash($release);
        $existingSource = VersionSource::query()->where($sourceKey)->first();
        $wasRecentlyCreated = $version === null;
        $sourceChanged = ! $existingSource || $existingSource->payload_hash !== $payloadHash;

        if (! $version) {
            $version = $this->versionService->create([
                'software_id' => $software->id,
                'version_number' => $versionNumber,
                'release_date' => $this->releaseDate($release['published_at'] ?? null),
            ]);
        }

        $content = $this->syncContent($version, $existingSource, $release);
        $versionSource = $existingSource ?? new VersionSource($sourceKey);
        $versionSource->fill([
            'version_id' => $version->id,
            'tag_name' => (string) ($release['tag_name'] ?? $versionNumber),
            'name' => $release['name'] ?? null,
            'body' => $release['body'] ?? null,
            'source_url' => $release['source_url'] ?? null,
            'source_updated_at' => $release['source_updated_at'] ?? $release['published_at'] ?? null,
            'payload_hash' => $payloadHash,
            'imported_content_hash' => $content['hash'],
            'is_prerelease' => (bool) ($release['is_prerelease'] ?? false),
            'last_seen_at' => now(),
        ])->save();

        return [
            'status' => $wasRecentlyCreated
                ? 'created'
                : ($sourceChanged ? 'updated' : 'unchanged'),
            'warning' => $content['warning'],
        ];
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array{hash:?string,warning:?string}
     */
    protected function syncContent(Version $version, ?VersionSource $source, array $release): array
    {
        $body = filled($release['body'] ?? null) ? (string) $release['body'] : null;

        if ($body === null) {
            return ['hash' => null, 'warning' => null];
        }

        $contentHash = hash('sha256', $body);
        $content = $version->textContents()
            ->where('language', Language::EN->value)
            ->first();
        $title = (string) ($release['name'] ?? $release['tag_name'] ?? $version->version_number);

        if (! $content) {
            TextContent::create([
                'version_id' => $version->id,
                'title' => $title,
                'content' => $body,
                'language' => Language::EN,
            ]);

            return ['hash' => $contentHash, 'warning' => null];
        }

        $previousHash = $source?->imported_content_hash;

        if ($previousHash === null || hash('sha256', (string) $content->content) === $previousHash) {
            $content->update([
                'title' => $title,
                'content' => $body,
            ]);

            return ['hash' => $contentHash, 'warning' => null];
        }

        return [
            'hash' => $previousHash,
            'warning' => 'Version '.$version->version_number.' has manually edited English release notes; the upstream change was kept for review.',
        ];
    }

    /**
     * @param  array<string, mixed>  $release
     */
    protected function findSource(Software $software, array $release): ?VersionSource
    {
        return VersionSource::query()
            ->where('software_id', $software->id)
            ->where('provider', 'github')
            ->where('source_kind', $this->sourceKind($release)->value)
            ->where('external_id', $this->externalId($release))
            ->first();
    }

    /**
     * @param  array<string, mixed>  $release
     */
    protected function hasSourceIdentity(array $release): bool
    {
        return array_key_exists('source_kind', $release)
            || array_key_exists('source', $release)
            || array_key_exists('external_id', $release);
    }

    /**
     * @param  array<string, mixed>  $release
     */
    protected function sourceKind(array $release): VersionSourceKind
    {
        $kind = $release['source_kind'] ?? null;

        if (is_string($kind) && ($sourceKind = VersionSourceKind::tryFrom($kind))) {
            return $sourceKind;
        }

        return str_ends_with((string) ($release['source'] ?? ''), '_tag')
            ? VersionSourceKind::TAG
            : VersionSourceKind::RELEASE;
    }

    /**
     * @param  array<string, mixed>  $release
     */
    protected function externalId(array $release): string
    {
        return (string) ($release['external_id'] ?? $release['id'] ?? $release['tag_name']);
    }

    /**
     * @param  array<string, mixed>  $release
     */
    protected function payloadHash(array $release): string
    {
        return hash('sha256', (string) json_encode([
            'tag_name' => $release['tag_name'] ?? null,
            'name' => $release['name'] ?? null,
            'body' => $release['body'] ?? null,
            'published_at' => $release['published_at'] ?? null,
            'source_url' => $release['source_url'] ?? null,
            'source_updated_at' => $release['source_updated_at'] ?? null,
            'is_prerelease' => (bool) ($release['is_prerelease'] ?? false),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
