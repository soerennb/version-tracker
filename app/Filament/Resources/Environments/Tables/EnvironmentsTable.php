<?php

namespace App\Filament\Resources\Environments\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EnvironmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('customer.name')->label('Customer')->placeholder('Unassigned'),
                TextColumn::make('name')
                    ->label(__('filament.environments.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('filament.environments.fields.code'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('is_production')
                    ->label(__('filament.environments.fields.is_production'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('filament.common.yes') : __('filament.common.no'))
                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray'),
                TextColumn::make('is_active')
                    ->label(__('filament.environments.fields.is_active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('filament.environments.active') : __('filament.environments.inactive'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('deployments_count')
                    ->label(__('filament.environments.fields.deployments'))
                    ->counts('deployments')
                    ->badge(),
                TextColumn::make('latestSuccessfulDeployment.version.version_number')
                    ->label(__('filament.environments.fields.current_version'))
                    ->placeholder(__('filament.common.not_available')),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('filament.environments.fields.is_active')),
                TernaryFilter::make('is_production')
                    ->label(__('filament.environments.fields.is_production')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
