<?php

namespace App\Filament\Resources\Software\RelationManagers;

use App\Enums\SupportStatus;
use App\Helpers\VersionHelper;
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
        $supportOptions = collect(SupportStatus::cases())
            ->mapWithKeys(fn (SupportStatus $status) => [$status->value => $status->label()])
            ->all();

        return $schema
            ->components([
                TextInput::make('version_number')
                    ->required()
                    ->regex(VersionHelper::semverRegex())
                    ->helperText('e.g. 1.2.3, 1.2.3-rc.1, 1.2.3+build.5'),
                DatePicker::make('release_date')
                    ->required(),
                DatePicker::make('eol_date'),
                DatePicker::make('lts_date'),
                Select::make('support_status')
                    ->options($supportOptions),
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
                    ->badge()
                    ->formatStateUsing(fn (?SupportStatus $state) => $state?->label())
                    ->color(fn (?SupportStatus $state) => $state?->color() ?? 'gray')
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
