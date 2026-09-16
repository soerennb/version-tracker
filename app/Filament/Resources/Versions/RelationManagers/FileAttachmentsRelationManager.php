<?php

namespace App\Filament\Resources\Versions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FileAttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'fileAttachments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('filename')
                    ->required(),
                Select::make('artifact_type')
                    ->label(__('filament.files.fields.artifact_type'))
                    ->options([
                        'release' => __('filament.files.artifact_types.release'),
                        'installer' => __('filament.files.artifact_types.installer'),
                        'container' => __('filament.files.artifact_types.container'),
                        'sbom' => __('filament.files.artifact_types.sbom'),
                        'signature' => __('filament.files.artifact_types.signature'),
                        'other' => __('filament.files.artifact_types.other'),
                    ]),
                TextInput::make('platform')
                    ->label(__('filament.files.fields.platform')),
                TextInput::make('architecture')
                    ->label(__('filament.files.fields.architecture')),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('mime_type')
                    ->required(),
                TextInput::make('size')
                    ->required()
                    ->numeric(),
                TextInput::make('checksum')
                    ->label(__('filament.files.fields.checksum')),
                Select::make('verification_status')
                    ->label(__('filament.files.fields.verification_status'))
                    ->options([
                        'unverified' => __('filament.files.verification_status.unverified'),
                        'pending' => __('filament.files.verification_status.pending'),
                        'verified' => __('filament.files.verification_status.verified'),
                        'failed' => __('filament.files.verification_status.failed'),
                    ])
                    ->required(),
                Toggle::make('is_public')
                    ->label(__('filament.files.fields.is_public'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                TextColumn::make('filename')
                    ->searchable(),
                TextColumn::make('artifact_type')
                    ->label(__('filament.files.fields.artifact_type'))
                    ->badge(),
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
