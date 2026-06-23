<?php

namespace App\Filament\Resources\Versions\Schemas;

use App\Enums\SupportStatus;
use App\Helpers\VersionHelper;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VersionForm
{
    public static function configure(Schema $schema): Schema
    {
        $supportOptions = collect(SupportStatus::cases())
            ->mapWithKeys(fn (SupportStatus $status) => [$status->value => $status->label()])
            ->all();

        return $schema
            ->components([
                Select::make('software_id')
                    ->label(__('filament.versions.fields.software'))
                    ->relationship('software', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('version_number')
                    ->label(__('filament.versions.fields.version_number'))
                    ->required()
                    ->regex(VersionHelper::semverRegex())
                    ->helperText('e.g. 1.2.3, 1.2.3-rc.1, 1.2.3+build.5'),
                DatePicker::make('release_date')
                    ->label(__('filament.versions.fields.release_date'))
                    ->required(),
                DatePicker::make('eol_date')
                    ->label(__('filament.versions.fields.eol_date')),
                DatePicker::make('lts_date')
                    ->label(__('filament.versions.fields.lts_date')),
                Select::make('support_status')
                    ->label(__('filament.versions.fields.support_status'))
                    ->options($supportOptions),
            ]);
    }
}
