<?php

namespace App\Filament\Resources\Versions\RelationManagers;

use App\Enums\ExploitabilityStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VulnerabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'vulnerabilities';

    public function form(Schema $schema): Schema
    {
        $severityOptions = collect(VulnerabilitySeverity::cases())
            ->mapWithKeys(fn (VulnerabilitySeverity $severity) => [$severity->value => $severity->label()])
            ->all();
        $statusOptions = collect(VulnerabilityStatus::cases())
            ->mapWithKeys(fn (VulnerabilityStatus $status) => [$status->value => $status->label()])
            ->all();
        $exploitabilityOptions = collect(ExploitabilityStatus::cases())
            ->mapWithKeys(fn (ExploitabilityStatus $status) => [$status->value => $status->label()])
            ->all();

        return $schema
            ->components([
                TextInput::make('cve_id')
                    ->required(),
                Select::make('severity')
                    ->options($severityOptions)
                    ->required(),
                TextInput::make('cvss_score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('source')
                    ->maxLength(255),
                TextInput::make('source_url')
                    ->url()
                    ->maxLength(255),
                TextInput::make('affected_range')
                    ->maxLength(255),
                Select::make('fixed_version_id')
                    ->relationship('fixedVersion', 'version_number')
                    ->searchable(),
                Select::make('status')
                    ->options($statusOptions)
                    ->default(VulnerabilityStatus::OPEN->value)
                    ->required(),
                Select::make('exploitability')
                    ->options($exploitabilityOptions)
                    ->default(ExploitabilityStatus::UNKNOWN->value)
                    ->required(),
                DatePicker::make('published_date')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cve_id')
            ->columns([
                TextColumn::make('cve_id')
                    ->searchable(),
                TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn (?VulnerabilitySeverity $state) => $state?->label())
                    ->color(fn (?VulnerabilitySeverity $state) => $state?->color() ?? 'gray')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?VulnerabilityStatus $state) => $state?->label())
                    ->color(fn (?VulnerabilityStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('exploitability')
                    ->badge()
                    ->formatStateUsing(fn (?ExploitabilityStatus $state) => $state?->label())
                    ->color(fn (?ExploitabilityStatus $state) => $state?->color() ?? 'gray')
                    ->toggleable(),
                TextColumn::make('published_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
