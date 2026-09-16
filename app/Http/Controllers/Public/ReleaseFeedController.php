<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Helpers\PublicLocale;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicTimelineRequest;
use App\Models\Version;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use JsonException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use XMLWriter;

class ReleaseFeedController extends Controller
{
    /**
     * Render a cacheable RSS feed containing only published releases.
     *
     * The feed is deliberately backed by the same public release data as the
     * pull API, so consumers can switch between polling and feed readers
     * without receiving drafts or private metadata.
     */
    public function __invoke(PublicTimelineRequest $request): Response
    {
        $releases = Version::query()
            ->with([
                'software:id,name',
                'textContents' => fn ($query) => $query->latest(),
            ])
            ->where('status', VersionStatus::PUBLISHED)
            ->latest('release_date')
            ->latest('id')
            ->limit(20)
            ->get([
                'id',
                'software_id',
                'version_number',
                'release_date',
                'created_at',
                'updated_at',
            ]);

        $etag = $this->etag($releases);
        $xml = $this->xml($request, $releases);
        $lastModified = $releases
            ->map(fn (Version $version) => $version->updated_at ?? $version->created_at)
            ->filter()
            ->sortDesc()
            ->first();

        $response = response($xml, SymfonyResponse::HTTP_OK, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ])->setEtag($etag);

        if ($lastModified) {
            $response->setLastModified($lastModified);
        }

        if ($request->header('If-None-Match') === $etag) {
            $response->setNotModified();
        }

        return $response;
    }

    /**
     * @param  Collection<int, Version>  $releases
     */
    private function xml(PublicTimelineRequest $request, Collection $releases): string
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('rss');
        $writer->writeAttribute('version', '2.0');
        $writer->startElement('channel');
        $writer->writeElement('title', config('app.name').' – Releases');
        $writer->writeElement('link', url('/'));
        $writer->writeElement('description', 'Published software releases.');
        $writer->writeElement('language', PublicLocale::requested($request)->value);

        $latest = $releases
            ->map(fn (Version $version) => $version->updated_at ?? $version->created_at)
            ->filter()
            ->sortDesc()
            ->first();
        if ($latest) {
            $writer->writeElement('lastBuildDate', $latest->toRfc2822String());
        }

        foreach ($releases as $release) {
            $content = PublicLocale::content($release->textContents, $request);
            $link = url('/api/public/releases/'.$release->id);
            $publishedAt = $release->release_date ?? $release->updated_at ?? $release->created_at ?? now();
            $description = trim(implode("\n\n", array_filter([
                $content?->title,
                $content?->content,
            ])));

            $writer->startElement('item');
            $writer->writeElement('title', trim(($release->software?->name ?? 'Release').' '.$release->version_number));
            $writer->writeElement('link', $link);
            $writer->writeElement('guid', 'versiontracker:release:'.$release->id);
            $writer->writeElement('pubDate', $publishedAt->toRfc2822String());
            $writer->writeElement('description', $description);
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * @param  Collection<int, Version>  $releases
     */
    private function etag(Collection $releases): string
    {
        try {
            $payload = $releases->map(fn (Version $version): array => [
                'id' => $version->id,
                'version' => $version->version_number,
                'release_date' => $version->release_date?->toISOString(),
                'updated_at' => $version->updated_at?->toISOString(),
                'notes' => $version->textContents->map(fn ($content): array => [
                    'language' => $content->language?->value,
                    'title' => $content->title,
                    'content' => $content->content,
                    'updated_at' => $content->updated_at?->toISOString(),
                ])->all(),
            ])->all();

            return '"'.sha1(json_encode($payload, JSON_THROW_ON_ERROR)).'"';
        } catch (JsonException) {
            return '"'.sha1((string) now()->timestamp).'"';
        }
    }
}
