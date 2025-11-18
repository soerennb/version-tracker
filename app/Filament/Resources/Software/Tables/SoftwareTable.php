<?php

namespace App\Filament\Resources\Software\Tables;

use App\Enums\SoftwareStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SoftwareTable
{
    public static function configure(Table $table): Table
    {
        $statusOptions = collect(SoftwareStatus::cases())
            ->mapWithKeys(fn (SoftwareStatus $status) => [$status->value => $status->label()])
            ->all();

        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.software.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => str($record->description)->limit(60)->toString()),
                TextColumn::make('status')
                    ->label(__('filament.software.fields.status'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?SoftwareStatus $state) => $state?->label())
                    ->color(fn (?SoftwareStatus $state) => match ($state) {
                        SoftwareStatus::ACTIVE => 'success',
                        SoftwareStatus::INACTIVE => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('versions_count')
                    ->label(__('filament.navigation.versions'))
                    ->counts('versions')
                    ->badge()
                    ->color('info'),
                TextColumn::make('current_version')
                    ->label(__('filament.software.fields.current_version'))
                    ->sortable(),
                TextColumn::make('compliance_status')
                    ->label(__('filament.software.fields.compliance_status'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'compliant' => 'success',
                        'non_compliant' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('last_release_date')
                    ->label(__('filament.software.fields.last_release_date'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('filament.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament.software.fields.status'))
                    ->options($statusOptions),
                SelectFilter::make('compliance_status')
                    ->label(__('filament.software.fields.compliance_status'))
                    ->options([
                        'compliant' => __('filament.software.compliance.compliant'),
                        'non_compliant' => __('filament.software.compliance.non_compliant'),
                        'unknown' => __('filament.software.compliance.unknown'),
                    ]),
                TrashedFilter::make(),
            ])
            ->recordUrl(null)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
