<?php

namespace App\Services;

use App\Models\Software;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GitHubReleaseClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function releasesAndTags(Software $software): array
    {
        $repository = $this->repositoryPath($software->github_repo_url);

        if (! $repository) {
            return [];
        }

        $releases = Http::acceptJson()
            ->withUserAgent((string) config('app.name', 'Versiontracker'))
            ->get('https://api.github.com/repos/'.$repository.'/releases')
            ->throw()
            ->json();

        $tags = Http::acceptJson()
            ->withUserAgent((string) config('app.name', 'Versiontracker'))
            ->get('https://api.github.com/repos/'.$repository.'/tags')
            ->throw()
            ->json();

        return collect(is_array($releases) ? $releases : [])
            ->map(fn (array $release): array => [
                'tag_name' => $release['tag_name'] ?? null,
                'name' => $release['name'] ?? null,
                'body' => $release['body'] ?? null,
                'published_at' => $release['published_at'] ?? null,
                'source_url' => $release['html_url'] ?? null,
                'source' => 'github_release',
            ])
            ->merge(collect(is_array($tags) ? $tags : [])
                ->map(fn (array $tag): array => [
                    'tag_name' => $tag['name'] ?? null,
                    'name' => $tag['name'] ?? null,
                    'body' => null,
                    'published_at' => null,
                    'source_url' => null,
                    'source' => 'github_tag',
                ]))
            ->unique('tag_name')
            ->values()
            ->all();
    }

    public function repositoryPath(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $path = trim(Str::replaceEnd('.git', '', $path), '/');
        $segments = explode('/', $path);

        if (count($segments) < 2) {
            return null;
        }

        return $segments[0].'/'.$segments[1];
    }
}
