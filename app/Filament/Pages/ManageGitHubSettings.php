<?php

namespace App\Filament\Pages;

use App\Settings\GitHubSettings;
use BackedEnum;
use DateTimeZone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGitHubSettings extends SettingsPage
{
    protected static string $requiredAbility = 'manage_advanced_settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static ?int $navigationSort = 80;

    protected static string $settings = GitHubSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.settings_advanced');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.settings_github');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.settings.github.connection'))
                    ->description(__('filament.settings.github.connection_description'))
                    ->columns(2)
                    ->components([
                        Toggle::make('sync_enabled')
                            ->label(__('filament.settings.fields.github_sync_enabled')),
                        TextInput::make('timeout')
                            ->label(__('filament.settings.fields.github_timeout'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(120),
                        TextInput::make('max_pages')
                            ->label(__('filament.settings.fields.github_max_pages'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100),
                        TimePicker::make('sync_time')
                            ->label(__('filament.settings.fields.github_sync_time'))
                            ->format('H:i')
                            ->seconds(false)
                            ->required(),
                        Select::make('sync_timezone')
                            ->label(__('filament.settings.fields.github_sync_timezone'))
                            ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                            ->searchable()
                            ->required()
                            ->native(false),
                    ]),
                Section::make(__('filament.settings.github.import'))
                    ->columns(2)
                    ->components([
                        Toggle::make('import_releases')
                            ->label(__('filament.settings.fields.github_import_releases')),
                        Toggle::make('import_tags')
                            ->label(__('filament.settings.fields.github_import_tags')),
                    ]),
            ]);
    }
}
