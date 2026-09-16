<?php

namespace App\Filament\Resources\FileAttachments\Schemas;

use App\Services\RuntimeSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FileAttachmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('version_id')
                    ->relationship('version', 'id')
                    ->searchable()
                    ->required(),
                TextInput::make('filename')
                    ->label(__('filament.files.fields.filename'))
                    ->readOnly()
                    ->dehydrated(),
                Select::make('artifact_type')
                    ->label(__('filament.files.fields.artifact_type'))
                    ->options([
                        'release' => __('filament.files.artifact_types.release'),
                        'installer' => __('filament.files.artifact_types.installer'),
                        'container' => __('filament.files.artifact_types.container'),
                        'sbom' => __('filament.files.artifact_types.sbom'),
                        'signature' => __('filament.files.artifact_types.signature'),
                        'other' => __('filament.files.artifact_types.other'),
                    ])
                    ->searchable(),
                TextInput::make('platform')
                    ->label(__('filament.files.fields.platform'))
                    ->placeholder('linux, windows, macOS'),
                TextInput::make('architecture')
                    ->label(__('filament.files.fields.architecture'))
                    ->placeholder('x86_64, arm64'),
                FileUpload::make('file_path')
                    ->label(__('filament.files.fields.file'))
                    ->disk(config('filesystems.default', 'local'))
                    ->directory('attachments')
                    ->maxSize(app(RuntimeSettings::class)->security()->upload_max_kb)
                    ->storeFileNamesIn('filename')
                    ->downloadable()
                    ->openable()
                    ->preserveFilenames(false)
                    ->preventFilePathTampering()
                    ->required(),
                TextInput::make('checksum')
                    ->label(__('filament.files.fields.checksum'))
                    ->maxLength(255),
                Select::make('checksum_algorithm')
                    ->label(__('filament.files.fields.checksum_algorithm'))
                    ->options([
                        'sha256' => 'SHA-256',
                        'sha512' => 'SHA-512',
                        'sha1' => 'SHA-1',
                    ]),
                Select::make('verification_status')
                    ->label(__('filament.files.fields.verification_status'))
                    ->options([
                        'unverified' => __('filament.files.verification_status.unverified'),
                        'pending' => __('filament.files.verification_status.pending'),
                        'verified' => __('filament.files.verification_status.verified'),
                        'failed' => __('filament.files.verification_status.failed'),
                    ])
                    ->required()
                    ->default('unverified'),
                Toggle::make('is_public')
                    ->label(__('filament.files.fields.is_public'))
                    ->default(true),
            ]);
    }
}
