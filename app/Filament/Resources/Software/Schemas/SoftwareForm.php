<?php

namespace App\Filament\Resources\Software\Schemas;

use App\Enums\ComplianceStatus;
use App\Enums\SoftwareStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SoftwareForm
{
    public static function configure(Schema $schema): Schema
    {
        $statusOptions = collect(SoftwareStatus::cases())
            ->mapWithKeys(fn (SoftwareStatus $status) => [$status->value => $status->label()])
            ->all();
        $complianceOptions = collect(ComplianceStatus::cases())
            ->mapWithKeys(fn (ComplianceStatus $status) => [$status->value => $status->label()])
            ->all();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament.software.fields.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament.software.fields.description'))
                    ->rows(4)
                    ->columnSpanFull(),
                Select::make('status')
                    ->label(__('filament.software.fields.status'))
                    ->options($statusOptions)
                    ->default(SoftwareStatus::ACTIVE->value)
                    ->required(),
                TextInput::make('license_type')
                    ->label(__('filament.software.fields.license_type'))
                    ->maxLength(255),
                Select::make('compliance_status')
                    ->label(__('filament.software.fields.compliance_status'))
                    ->options($complianceOptions)
                    ->default(ComplianceStatus::UNKNOWN->value)
                    ->required(),
                TextInput::make('github_repo_url')
                    ->label(__('filament.software.fields.github_repo_url'))
                    ->url()
                    ->maxLength(255),
            ]);
    }
}
