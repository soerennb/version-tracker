<?php

namespace App\Services;

use App\Models\Software;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GitHubReleaseClient
{
    public function __construct(private readonly RuntimeSettings $runtimeSettings) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function releasesAndTags(Software $software): array
    {
        $repository = $this->repositoryPath($software->github_repo_url);

        if (! $repository) {
            return [];
        }

        $settings = $this->runtimeSettings->github();
        $releases = $settings->import_releases ? $this->getPages('repos/'.$repository.'/releases') : [];
        $tags = $settings->import_tags ? $this->getPages('repos/'.$repository.'/tags') : [];
        $entries = [];

        foreach ($releases as $release) {
            if (($release['draft'] ?? false) || blank($release['tag_name'] ?? null)) {
                continue;
            }

            $entry = [
                'tag_name' => (string) $release['tag_name'],
                'name' => $release['name'] ?? null,
                'body' => $release['body'] ?? null,
                'published_at' => $release['published_at'] ?? null,
                'source_url' => $release['html_url'] ?? null,
                'source' => 'github_release',
                'source_kind' => 'release',
                'external_id' => (string) ($release['id'] ?? $release['tag_name']),
                'source_updated_at' => $release['updated_at'] ?? $release['published_at'] ?? null,
                'is_prerelease' => (bool) ($release['prerelease'] ?? false),
            ];

            $entries[Str::lower($entry['tag_name'])] = $entry;
        }

        foreach ($tags as $tag) {
            $tagName = $tag['name'] ?? null;

            if (! is_string($tagName) || blank($tagName)) {
                continue;
            }

            $key = Str::lower($tagName);

            if (isset($entries[$key])) {
                continue;
            }

            $entries[$key] = [
                'tag_name' => $tagName,
                'name' => $tagName,
                'body' => null,
                'published_at' => null,
                'source_url' => 'https://github.com/'.$repository.'/releases',
                'source' => 'github_tag',
                'source_kind' => 'tag',
                'external_id' => $tagName,
                'source_updated_at' => null,
                'is_prerelease' => false,
            ];
        }

        return array_values($entries);
    }

    public function repositoryPath(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($host) || ! in_array(Str::lower($host), ['github.com', 'www.github.com'], true)) {
            return null;
        }

        if (! is_string($path)) {
            return null;
        }

        $path = trim(Str::replaceEnd('.git', '', $path), '/');
        $segments = explode('/', $path);

        if (count($segments) !== 2 || in_array('', $segments, true)) {
            return null;
        }

        return $segments[0].'/'.$segments[1];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getPages(string $path): array
    {
        $items = [];
        $maxPages = max($this->runtimeSettings->github()->max_pages, 1);

        for ($page = 1; $page <= $maxPages; $page++) {
            $pageItems = $this->request()
                ->get('https://api.github.com/'.$path, [
                    'per_page' => 100,
                    'page' => $page,
                ])
                ->throw()
                ->json();

            if (! is_array($pageItems)) {
                break;
            }

            $items = [...$items, ...array_values(array_filter($pageItems, 'is_array'))];

            if (count($pageItems) < 100) {
                break;
            }
        }

        return $items;
    }

    protected function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->withUserAgent($this->runtimeSettings->general()->application_name)
            ->timeout(max($this->runtimeSettings->github()->timeout, 1))
            ->retry([250, 750, 1500]);

        $token = config('services.github.token');

        return filled($token) ? $request->withToken((string) $token) : $request;
    }
}
