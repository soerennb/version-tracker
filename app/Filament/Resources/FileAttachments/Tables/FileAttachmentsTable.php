<?php

namespace App\Filament\Resources\FileAttachments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FileAttachmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version.id')
                    ->searchable(),
                TextColumn::make('filename')
                    ->searchable(),
                TextColumn::make('artifact_type')
                    ->label(__('filament.files.fields.artifact_type'))
                    ->badge(),
                TextColumn::make('platform')
                    ->label(__('filament.files.fields.platform'))
                    ->toggleable(),
                TextColumn::make('architecture')
                    ->label(__('filament.files.fields.architecture'))
                    ->toggleable(),
                TextColumn::make('file_path')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->searchable(),
                TextColumn::make('size')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('verification_status')
                    ->label(__('filament.files.fields.verification_status'))
                    ->badge(),
                TextColumn::make('is_public')
                    ->label(__('filament.files.fields.is_public'))
                    ->boolean(),
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
