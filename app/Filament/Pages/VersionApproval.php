<?php

namespace App\Filament\Pages;

use App\Enums\ApprovalStatus;
use App\Models\Version;
use App\Services\VersionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class VersionApproval extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.version-approval';

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.approval');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament.navigation.approval'))
            ->emptyStateHeading(__('filament.messages.approval_empty'))
            ->striped()
            ->query(
                Version::query()
                    ->with('software')
                    ->where('approval_status', ApprovalStatus::PENDING->value)
            )
            ->columns([
                TextColumn::make('software.name')
                    ->label(__('filament.versions.fields.software'))
                    ->searchable(),
                TextColumn::make('version_number')
                    ->label(__('filament.versions.fields.version_number'))
                    ->badge()
                    ->color('info'),
                TextColumn::make('release_date')
                    ->label(__('filament.versions.fields.release_date'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('filament.versions.fields.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('filament.fields.created_at'))
                    ->since(),
            ])
            ->actions([
                Action::make('approve')
                    ->label(__('filament.actions.approve'))
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Version $record) => $this->approveVersion($record)),
                Action::make('reject')
                    ->label(__('filament.actions.reject'))
                    ->color('danger')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(__('filament.actions.reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(fn (Version $record, array $data) => $this->rejectVersion($record, $data['reason'] ?? null)),
            ])
            ->poll('45s');
    }

    protected function approveVersion(Version $version): void
    {
        app(VersionService::class)->approve($version);

        Notification::make()
            ->title(__('filament.messages.version_approved'))
            ->body($version->software?->name.' '.$version->version_number)
            ->success()
            ->send();
    }

    protected function rejectVersion(Version $version, ?string $reason): void
    {
        app(VersionService::class)->reject($version, $reason);

        Notification::make()
            ->title(__('filament.messages.version_rejected'))
            ->body($reason)
            ->danger()
            ->send();
    }
}
