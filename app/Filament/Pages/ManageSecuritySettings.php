<?php

namespace App\Filament\Pages;

use App\Settings\SecuritySettings;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSecuritySettings extends SettingsPage
{
    protected static string $requiredAbility = 'manage_advanced_settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 81;

    protected static string $settings = SecuritySettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.settings_advanced');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.settings_security');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.settings.security.api'))
                    ->columns(2)
                    ->components([
                        TextInput::make('api_rate_limit_per_minute')
                            ->label(__('filament.settings.fields.api_rate_limit_per_minute'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000),
                        Toggle::make('force_hsts')
                            ->label(__('filament.settings.fields.force_hsts')),
                    ]),
                Section::make(__('filament.settings.security.uploads'))
                    ->columns(2)
                    ->components([
                        TextInput::make('upload_max_kb')
                            ->label(__('filament.settings.fields.upload_max_kb'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1048576),
                        CheckboxList::make('upload_allowed_extensions')
                            ->label(__('filament.settings.fields.upload_allowed_extensions'))
                            ->options([
                                'pdf' => 'PDF',
                                'txt' => 'TXT',
                                'csv' => 'CSV',
                                'json' => 'JSON',
                                'xml' => 'XML',
                                'md' => 'Markdown',
                                'doc' => 'DOC',
                                'docx' => 'DOCX',
                                'xls' => 'XLS',
                                'xlsx' => 'XLSX',
                                'png' => 'PNG',
                                'jpg' => 'JPG',
                                'jpeg' => 'JPEG',
                                'zip' => 'ZIP',
                            ])
                            ->columns(3)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['upload_allowed_extensions'] = array_values(array_unique(array_map(
            static fn (string $extension): string => strtolower(trim($extension)),
            $data['upload_allowed_extensions'] ?? [],
        )));

        return $data;
    }
}
