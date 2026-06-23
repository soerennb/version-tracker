<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TextContentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_update_text_content(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);
        Sanctum::actingAs($user);

        $textContent = TextContent::factory()->create();

        $this->putJson('/api/text-contents/'.$textContent->id, [
            'title' => 'Updated title',
        ])->assertForbidden();

        $this->assertNotSame('Updated title', $textContent->refresh()->title);
    }

    public function test_user_with_edit_content_ability_can_update_text_content(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_content'],
        ]);
        Sanctum::actingAs($user);

        $textContent = TextContent::factory()->create();

        $this->putJson('/api/text-contents/'.$textContent->id, [
            'title' => 'Updated title',
        ])->assertOk();

        $this->assertSame('Updated title', $textContent->refresh()->title);
    }

    public function test_software_owner_can_update_text_content(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);
        Sanctum::actingAs($owner);

        $software = Software::factory()->create([
            'created_by' => $owner->id,
        ]);
        $version = Version::factory()->create([
            'software_id' => $software->id,
        ]);
        $textContent = TextContent::factory()->create([
            'version_id' => $version->id,
        ]);

        $this->putJson('/api/text-contents/'.$textContent->id, [
            'title' => 'Owner updated title',
        ])->assertOk();

        $this->assertSame('Owner updated title', $textContent->refresh()->title);
    }
}
