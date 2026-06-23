<?php

namespace App\Filament\Resources\Versions\Pages;

use App\Enums\Language;
use App\Filament\Resources\Versions\VersionResource;
use App\Models\AuditLog;
use App\Models\TextContent;
use App\Models\Version;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ReleaseNotesEditor extends Page
{
    protected static string $resource = VersionResource::class;

    protected string $view = 'filament.resources.versions.pages.release-notes-editor';

    public Version|int|string $record;

    /** @var array<string, array{title:string,content:string}> */
    public array $notes = [];

    public function mount(int|string $record): void
    {
        $this->record = Version::query()->with('textContents')->findOrFail($record);
        Gate::authorize('update', $this->record);

        foreach (Language::cases() as $language) {
            $content = $this->record->textContents->firstWhere('language', $language);
            $this->notes[$language->value] = [
                'title' => $content?->title ?? '',
                'content' => $content?->content ?? '',
            ];
        }
    }

    public function save(): void
    {
        Gate::authorize('update', $this->record);

        $rules = [];
        foreach (Language::values() as $language) {
            $rules["notes.{$language}.title"] = ['required', 'string', 'max:255'];
            $rules["notes.{$language}.content"] = ['required', 'string', 'max:100000'];
        }
        $this->validate($rules);

        foreach (Language::cases() as $language) {
            $this->record->textContents()->updateOrCreate(
                ['language' => $language],
                $this->notes[$language->value],
            );
        }

        $this->record->load('textContents');

        Notification::make()
            ->title(__('filament.release_notes.saved'))
            ->success()
            ->send();
    }

    public function isComplete(): bool
    {
        return collect(Language::values())->every(fn (string $language): bool => filled($this->notes[$language]['title'] ?? null)
            && filled($this->notes[$language]['content'] ?? null));
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function recentChanges(): Collection
    {
        $contentIds = $this->record->textContents()->pluck('id');

        return AuditLog::query()
            ->with('user:id,name')
            ->where('model_type', TextContent::class)
            ->whereIn('model_id', $contentIds)
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    public function getTitle(): string
    {
        return __('filament.release_notes.title', ['version' => $this->record->version_number]);
    }
}
