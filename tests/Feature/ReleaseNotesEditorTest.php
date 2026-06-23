<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Filament\Resources\Versions\Pages\ReleaseNotesEditor;
use App\Models\AuditLog;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReleaseNotesEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_editor_saves_both_languages_and_reports_completeness(): void
    {
        $version = Version::factory()->create();

        Livewire::test(ReleaseNotesEditor::class, ['record' => $version->id])
            ->assertSee('Deutsch')
            ->assertSee('English')
            ->assertSee(__('filament.release_notes.incomplete'))
            ->set('notes.de.title', 'Deutsche Hinweise')
            ->set('notes.de.content', 'Inhalt auf Deutsch')
            ->set('notes.en.title', 'English notes')
            ->set('notes.en.content', 'Content in English')
            ->assertSee(__('filament.release_notes.complete'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('text_contents', [
            'version_id' => $version->id,
            'language' => Language::DE->value,
            'title' => 'Deutsche Hinweise',
        ]);
        $this->assertDatabaseHas('text_contents', [
            'version_id' => $version->id,
            'language' => Language::EN->value,
            'title' => 'English notes',
        ]);
    }

    public function test_text_content_updates_create_field_level_audit_diff(): void
    {
        $version = Version::factory()->create();
        $content = TextContent::factory()->for($version)->create([
            'language' => Language::DE,
            'title' => 'Before',
            'content' => 'Old content',
        ]);
        AuditLog::query()->delete();

        Livewire::test(ReleaseNotesEditor::class, ['record' => $version->id])
            ->set('notes.de.title', 'After')
            ->set('notes.de.content', 'New content')
            ->set('notes.en.title', 'English')
            ->set('notes.en.content', 'English content')
            ->call('save')
            ->assertHasNoErrors();

        $audit = AuditLog::query()
            ->where('model_type', TextContent::class)
            ->where('model_id', $content->id)
            ->where('action', 'text_content.updated')
            ->firstOrFail();

        $this->assertSame('Before', $audit->old_values['title']);
        $this->assertSame('After', $audit->new_values['title']);
        $this->assertArrayNotHasKey('version_id', $audit->old_values);
    }
}
