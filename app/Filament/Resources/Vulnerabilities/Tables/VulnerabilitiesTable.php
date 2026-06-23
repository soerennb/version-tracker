<?php

namespace App\Filament\Resources\Vulnerabilities\Tables;

use App\Enums\ExploitabilityStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VulnerabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cve_id')
                    ->label(__('vulnerabilities.fields.cve'))
                    ->searchable(),
                TextColumn::make('affectedVersion.software.name')
                    ->label(__('vulnerabilities.fields.software'))
                    ->description(fn ($record): ?string => $record->affectedVersion?->version_number)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('severity')
                    ->label(__('vulnerabilities.fields.severity'))
                    ->badge()
                    ->formatStateUsing(fn (?VulnerabilitySeverity $state) => $state?->label())
                    ->color(fn (?VulnerabilitySeverity $state) => $state?->color() ?? 'gray')
                    ->searchable(),
                TextColumn::make('cvss_score')
                    ->label(__('vulnerabilities.fields.cvss'))
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('filament.versions.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?VulnerabilityStatus $state) => $state?->label())
                    ->color(fn (?VulnerabilityStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('exploitability')
                    ->label(__('vulnerabilities.fields.exploitability'))
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
