<?php

namespace App\Filament\Pages;

use App\Enums\ApprovalStatus;
use App\Enums\RejectReason;
use App\Models\Version;
use App\Services\ApprovalCockpitService;
use App\Services\ReleaseReadinessService;
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

    public ?int $selectedVersionId = null;

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.approval');
    }

    public function mount(): void
    {
        $this->selectedVersionId = Version::query()
            ->where('approval_status', ApprovalStatus::PENDING->value)
            ->oldest('created_at')
            ->value('id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament.navigation.approval'))
            ->emptyStateHeading(__('filament.messages.approval_empty'))
            ->striped()
            ->query(
                Version::query()
                    ->with([
                        'fileAttachments',
                        'software',
                        'software.dependenciesOutgoing.dependsOnSoftware',
                        'software.dependenciesOutgoing.minVersion',
                        'software.dependenciesOutgoing.maxVersion',
                        'textContents',
                        'vulnerabilities',
                    ])
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
                TextColumn::make('readiness')
                    ->label(__('versions.readiness.label'))
                    ->state(fn (Version $record): string => app(ReleaseReadinessService::class)->evaluate($record)['score'].'%')
                    ->badge()
                    ->color(fn (Version $record): string => app(ReleaseReadinessService::class)->evaluate($record)['is_ready'] ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label(__('filament.fields.created_at'))
                    ->since(),
            ])
            ->actions([
                Action::make('review')
                    ->label(__('filament.actions.review'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->action(fn (Version $record) => $this->selectVersion($record)),
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
                        Forms\Components\Select::make('reject_reason')
                            ->label(__('versions.review.reject_reason'))
                            ->options(collect(RejectReason::cases())->mapWithKeys(fn (RejectReason $reason): array => [
                                $reason->value => $reason->label(),
                            ]))
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label(__('filament.actions.reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(fn (Version $record, array $data) => $this->rejectVersion($record, $data['reason'] ?? null, $data['reject_reason'] ?? null)),
            ])
            ->poll('45s');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $selectedVersion = $this->selectedVersion();

        return [
            'cockpit' => $selectedVersion
                ? app(ApprovalCockpitService::class)->build($selectedVersion)
                : null,
        ];
    }

    public function selectVersion(Version $version): void
    {
        $this->selectedVersionId = $version->id;
    }

    protected function approveVersion(Version $version): void
    {
        app(VersionService::class)->approve($version);

        if ($this->selectedVersionId === $version->id) {
            $this->selectedVersionId = null;
        }

        Notification::make()
            ->title(__('filament.messages.version_approved'))
            ->body($version->software?->name.' '.$version->version_number)
            ->success()
            ->send();
    }

    protected function rejectVersion(Version $version, ?string $reason, ?string $rejectReason): void
    {
        app(VersionService::class)->reject($version, $reason, $rejectReason);

        if ($this->selectedVersionId === $version->id) {
            $this->selectedVersionId = null;
        }

        Notification::make()
            ->title(__('filament.messages.version_rejected'))
            ->body($reason)
            ->danger()
            ->send();
    }

    protected function selectedVersion(): ?Version
    {
        if (! $this->selectedVersionId) {
            return null;
        }

        return Version::query()
            ->where('approval_status', ApprovalStatus::PENDING->value)
            ->find($this->selectedVersionId);
    }
}
