<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Enums\UserRole;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TextContentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_a_duplicate_language_for_one_release(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_content'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create();
        TextContent::factory()->for($version)->create(['language' => Language::DE]);

        $this->postJson('/api/versions/'.$version->id.'/text-contents', [
            'title' => 'Duplicate',
            'content' => 'This language already exists.',
            'language' => Language::DE->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('language');
    }

    public function test_update_can_keep_its_language_but_cannot_take_another_content_language(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['edit_content'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create();
        $german = TextContent::factory()->for($version)->create(['language' => Language::DE]);
        TextContent::factory()->for($version)->create(['language' => Language::EN]);

        $this->putJson('/api/text-contents/'.$german->id, [
            'language' => Language::DE->value,
            'title' => 'Updated German title',
        ])->assertOk();

        $this->putJson('/api/text-contents/'.$german->id, [
            'language' => Language::EN->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('language');

        $this->assertDatabaseHas('text_contents', [
            'id' => $german->id,
            'title' => 'Updated German title',
            'language' => Language::DE->value,
        ]);
    }
}
