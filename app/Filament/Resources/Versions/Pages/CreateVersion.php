<?php

namespace App\Filament\Resources\Versions\Pages;

use App\Filament\Resources\Versions\VersionResource;
use App\Models\Version;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVersion extends CreateRecord
{
    protected static string $resource = VersionResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->prefillFromDuplicate();
    }

    protected function prefillFromDuplicate(): void
    {
        $duplicateId = request()->integer('duplicate');

        if (! $duplicateId) {
            return;
        }

        $source = Version::query()->find($duplicateId);

        if (! $source) {
            Notification::make()
                ->danger()
                ->title(__('filament.messages.duplicate_missing'))
                ->send();

            return;
        }

        $this->form->fill($this->duplicateFormData($source));
    }

    protected function duplicateFormData(Version $source): array
    {
        return [
            'software_id' => $source->software_id,
            'version_number' => $source->version_number,
            'release_date' => optional($source->release_date)?->toDateString(),
            'status' => $source->status?->value,
            'approval_status' => $source->approval_status?->value,
            'eol_date' => optional($source->eol_date)?->toDateString(),
            'lts_date' => optional($source->lts_date)?->toDateString(),
            'support_status' => $source->support_status?->value,
        ];
    }
}
