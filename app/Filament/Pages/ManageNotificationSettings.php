<?php

namespace App\Filament\Pages;

use App\Settings\NotificationSettings;
use BackedEnum;
use DateTimeZone;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageNotificationSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 72;

    protected static string $settings = NotificationSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.settings_basis');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.settings_notifications');
    }

    public function form(Schema $schema): Schema
    {
        $channels = [
            'mail' => __('filament.settings.notification_channels.mail'),
            'database' => __('filament.settings.notification_channels.database'),
        ];

        return $schema
            ->components([
                Section::make(__('filament.settings.notifications.events'))
                    ->description(__('filament.settings.notifications.events_description'))
                    ->columns(2)
                    ->components([
                        Toggle::make('release_approved_enabled')
                            ->label(__('filament.settings.fields.release_approved_enabled')),
                        CheckboxList::make('release_approved_channels')
                            ->label(__('filament.settings.fields.channels'))
                            ->options($channels)
                            ->columns(2),
                        Toggle::make('release_published_enabled')
                            ->label(__('filament.settings.fields.release_published_enabled')),
                        CheckboxList::make('release_published_channels')
                            ->label(__('filament.settings.fields.channels'))
                            ->options($channels)
                            ->columns(2),
                        Toggle::make('security_alert_enabled')
                            ->label(__('filament.settings.fields.security_alert_enabled')),
                        CheckboxList::make('security_alert_channels')
                            ->label(__('filament.settings.fields.channels'))
                            ->options($channels)
                            ->columns(2),
                        Toggle::make('fix_available_enabled')
                            ->label(__('filament.settings.fields.fix_available_enabled')),
                        CheckboxList::make('fix_available_channels')
                            ->label(__('filament.settings.fields.channels'))
                            ->options($channels)
                            ->columns(2),
                        Toggle::make('lifecycle_alert_enabled')
                            ->label(__('filament.settings.fields.lifecycle_alert_enabled')),
                        CheckboxList::make('lifecycle_alert_channels')
                            ->label(__('filament.settings.fields.channels'))
                            ->options($channels)
                            ->columns(2),
                    ]),
                Section::make(__('filament.settings.notifications.lifecycle'))
                    ->columns(2)
                    ->components([
                        CheckboxList::make('eol_alert_windows')
                            ->label(__('filament.settings.fields.eol_alert_windows'))
                            ->options([
                                7 => __('filament.settings.eol_windows.seven'),
                                30 => __('filament.settings.eol_windows.thirty'),
                                90 => __('filament.settings.eol_windows.ninety'),
                            ])
                            ->columns(3)
                            ->required(),
                        TextInput::make('eol_alert_horizon_days')
                            ->label(__('filament.settings.fields.eol_alert_horizon_days'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(730),
                        TimePicker::make('lifecycle_alert_time')
                            ->label(__('filament.settings.fields.lifecycle_alert_time'))
                            ->format('H:i')
                            ->seconds(false)
                            ->required(),
                        Select::make('lifecycle_alert_timezone')
                            ->label(__('filament.settings.fields.lifecycle_alert_timezone'))
                            ->options(array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()))
                            ->searchable()
                            ->required()
                            ->native(false),
                    ]),
                Section::make(__('filament.settings.notifications.mail'))
                    ->columns(2)
                    ->components([
                        TextInput::make('mail_from_address')
                            ->label(__('filament.settings.fields.mail_from_address'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('mail_from_name')
                            ->label(__('filament.settings.fields.mail_from_name'))
                            ->required()
                            ->maxLength(120),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $windows = array_values(array_unique(array_map('intval', $data['eol_alert_windows'] ?? [])));
        sort($windows, SORT_NUMERIC);
        $data['eol_alert_windows'] = $windows;

        return $data;
    }
}
