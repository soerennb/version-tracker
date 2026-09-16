<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Enums\VersionStatus;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicReleaseFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_contains_published_releases_and_supports_conditional_requests(): void
    {
        $software = Software::factory()->create(['name' => 'Acme Platform']);
        $published = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '1.2.3',
            'release_date' => '2026-09-15',
        ]);
        TextContent::factory()->for($published)->create([
            'language' => Language::EN,
            'title' => 'Security update',
            'content' => 'Fixes <important> issues.',
        ]);
        Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'version_number' => '9.9.9',
        ]);

        $response = $this->get('/feed/releases.xml?locale=en');

        $response->assertOk()
            ->assertHeader('content-type', 'application/rss+xml; charset=UTF-8')
            ->assertHeader('cache-control', 'max-age=300, public');
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $this->assertSame('Acme Platform 1.2.3', (string) $xml->channel->item->title);
        $this->assertStringContainsString('Security update', (string) $xml->channel->item->description);
        $this->assertStringNotContainsString('9.9.9', $response->getContent());

        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);

        $this->get('/feed/releases.xml?locale=en', ['If-None-Match' => $etag])
            ->assertNotModified();
    }
}
