<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGeneralSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 70;

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.settings_basis');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.settings_general');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.settings.general.identity'))
                    ->description(__('filament.settings.general.identity_description'))
                    ->components([
                        TextInput::make('application_name')
                            ->label(__('filament.settings.fields.application_name'))
                            ->required()
                            ->maxLength(120),
                        TextInput::make('support_url')
                            ->label(__('filament.settings.fields.support_url'))
                            ->url()
                            ->nullable()
                            ->maxLength(255),
                    ]),
                Section::make(__('filament.settings.general.public_copy'))
                    ->description(__('filament.settings.general.public_copy_description'))
                    ->columns(2)
                    ->components([
                        TextInput::make('tagline_de')
                            ->label(__('filament.settings.fields.tagline_de'))
                            ->required()
                            ->maxLength(160),
                        TextInput::make('tagline_en')
                            ->label(__('filament.settings.fields.tagline_en'))
                            ->required()
                            ->maxLength(160),
                        Textarea::make('intro_de')
                            ->label(__('filament.settings.fields.intro_de'))
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                        Textarea::make('intro_en')
                            ->label(__('filament.settings.fields.intro_en'))
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                        TextInput::make('footer_de')
                            ->label(__('filament.settings.fields.footer_de'))
                            ->required()
                            ->maxLength(200),
                        TextInput::make('footer_en')
                            ->label(__('filament.settings.fields.footer_en'))
                            ->required()
                            ->maxLength(200),
                    ]),
                Section::make(__('filament.settings.general.localization'))
                    ->columns(2)
                    ->components([
                        TextInput::make('default_locale')
                            ->label(__('filament.settings.fields.default_locale'))
                            ->required()
                            ->in(['de', 'en']),
                        TextInput::make('fallback_locale')
                            ->label(__('filament.settings.fields.fallback_locale'))
                            ->required()
                            ->in(['de', 'en']),
                    ]),
                Section::make(__('filament.settings.general.public_modules'))
                    ->description(__('filament.settings.general.public_modules_description'))
                    ->columns(2)
                    ->components([
                        Toggle::make('public_catalog_enabled')
                            ->label(__('filament.settings.fields.public_catalog_enabled')),
                        Toggle::make('public_search_enabled')
                            ->label(__('filament.settings.fields.public_search_enabled')),
                        Toggle::make('public_products_enabled')
                            ->label(__('filament.settings.fields.public_products_enabled')),
                        Toggle::make('public_timeline_enabled')
                            ->label(__('filament.settings.fields.public_timeline_enabled')),
                        Toggle::make('public_security_enabled')
                            ->label(__('filament.settings.fields.public_security_enabled')),
                        Toggle::make('public_compare_enabled')
                            ->label(__('filament.settings.fields.public_compare_enabled')),
                    ]),
            ]);
    }
}
