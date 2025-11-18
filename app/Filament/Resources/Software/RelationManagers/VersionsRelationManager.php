<?php

namespace App\Filament\Resources\Software\RelationManagers;

use App\Enums\ApprovalStatus;
use App\Enums\VersionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('version_number')
                    ->required(),
                DatePicker::make('release_date')
                    ->required(),
                Select::make('status')
                    ->options(VersionStatus::class)
                    ->default('draft')
                    ->required(),
                Select::make('approval_status')
                    ->options(ApprovalStatus::class)
                    ->default('pending')
                    ->required(),
                DatePicker::make('eol_date'),
                DatePicker::make('lts_date'),
                TextInput::make('support_status'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->columns([
                TextColumn::make('version_number')
                    ->searchable(),
                TextColumn::make('release_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('approval_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('eol_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('lts_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('support_status')
                    ->searchable(),
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
