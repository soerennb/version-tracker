<?php

namespace App\Filament\Resources\Versions\Pages;

use App\Filament\Resources\Versions\VersionResource;
use App\Models\ComponentVersion;
use App\Services\ReleaseCompositionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;

class EditVersion extends EditRecord
{
    protected static string $resource = VersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('composition')
                ->label(__('filament.composition.edit_release'))
                ->icon('heroicon-o-squares-2x2')
                ->visible(fn (): bool => (bool) $this->record->software?->tracks_release_composition && $this->record->status?->isDraft() && (auth()->user()?->can('update', $this->record) ?? false))
                ->fillForm(function (): array {
                    $composition = $this->record->composition;

                    return [
                        'baseline_version_id' => $composition?->baseline_version_id,
                        'eforms_component_version_id' => $composition?->eforms_component_version_id,
                        'active_eforms_sdk_version_id' => $composition?->active_eforms_sdk_version_id,
                        'supported_interface_version_ids' => $composition?->supportedInterfaces()->pluck('component_version_id')->all() ?? [],
                    ];
                })
                ->form([
                    Select::make('baseline_version_id')->label(__('filament.composition.baseline_version'))->options(fn (): array => self::componentOptions('baseline'))->required()->searchable(),
                    Select::make('eforms_component_version_id')->label(__('filament.composition.eforms_component_version'))->options(fn (): array => self::componentOptions('eforms_component'))->required()->searchable(),
                    Select::make('active_eforms_sdk_version_id')->label(__('filament.composition.eforms_sdk_version'))->options(fn (): array => self::componentOptions('eforms_sdk'))->required()->searchable(),
                    Select::make('supported_interface_version_ids')->label(__('filament.composition.supported_versions'))->multiple()->options(fn (): array => self::interfaceOptions())->required()->searchable(),
                ])
                ->action(fn (array $data) => app(ReleaseCompositionService::class)->save($this->record, $data)),
            Action::make('releaseNotes')
                ->label(__('filament.release_notes.title_short'))
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => VersionResource::getUrl('release-notes', ['record' => $this->record])),
            DeleteAction::make(),
        ];
    }

    /** @return array<int, string> */
    private static function componentOptions(string $kind): array
    {
        return ComponentVersion::query()->with('component')->whereHas('component', fn ($query) => $query->where('kind', $kind)->whereNull('customer_id'))
            ->get()->mapWithKeys(fn (ComponentVersion $version): array => [$version->id => $version->component->name.' · '.$version->version_label])->all();
    }

    /** @return array<int, string> */
    private static function interfaceOptions(): array
    {
        return ComponentVersion::query()->with('component')->whereHas('component', fn ($query) => $query->whereIn('kind', ['interface', 'eforms_sdk'])->whereNull('customer_id'))
            ->get()->mapWithKeys(fn (ComponentVersion $version): array => [$version->id => $version->component->name.' · '.$version->version_label])->all();
    }
}
