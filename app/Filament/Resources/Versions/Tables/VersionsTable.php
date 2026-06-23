<?php

namespace App\Filament\Resources\Versions\Tables;

use App\Enums\ApprovalStatus;
use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Filament\Resources\Versions\Pages\CreateVersion;
use App\Models\Version;
use App\Services\ReleaseReadinessService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VersionsTable
{
    public static function configure(Table $table): Table
    {
        $statusOptions = collect(VersionStatus::cases())->mapWithKeys(fn (VersionStatus $case) => [$case->value => $case->label()])->all();
        $approvalOptions = collect(ApprovalStatus::cases())->mapWithKeys(fn (ApprovalStatus $case) => [$case->value => $case->label()])->all();

        return $table
            ->defaultSort('release_date', 'desc')
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['fileAttachments', 'textContents', 'vulnerabilities', 'software.dependenciesOutgoing.dependsOnSoftware', 'software.dependenciesOutgoing.minVersion', 'software.dependenciesOutgoing.maxVersion'])
                ->withCount(['vulnerabilities as security_blockers_count' => fn ($query) => $query
                    ->where('status', VulnerabilityStatus::OPEN)
                    ->whereIn('severity', [VulnerabilitySeverity::CRITICAL, VulnerabilitySeverity::HIGH])]))
            ->columns([
                TextColumn::make('software.name')
                    ->label(__('filament.versions.fields.software'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version_number')
                    ->label(__('filament.versions.fields.version_number'))
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('release_date')
                    ->label(__('filament.versions.fields.release_date'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('filament.versions.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?VersionStatus $state) => $state?->label())
                    ->color(fn (?VersionStatus $state) => $state === VersionStatus::PUBLISHED ? 'success' : 'warning'),
                TextColumn::make('approval_status')
                    ->label(__('filament.versions.fields.approval_status'))
                    ->badge()
                    ->formatStateUsing(fn (?ApprovalStatus $state) => $state?->label())
                    ->color(fn (?ApprovalStatus $state) => match ($state) {
                        ApprovalStatus::APPROVED => 'success',
                        ApprovalStatus::REJECTED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('readiness')
                    ->label(__('versions.readiness.label'))
                    ->state(fn (Version $record): string => app(ReleaseReadinessService::class)->evaluate($record)['score'].'%')
                    ->badge()
                    ->color(fn (Version $record): string => app(ReleaseReadinessService::class)->evaluate($record)['is_ready'] ? 'success' : 'warning'),
                TextColumn::make('security_blockers_count')
                    ->label(__('filament.versions.fields.security'))
                    ->badge()
                    ->formatStateUsing(fn (int|string|null $state): string => (int) $state === 0
                        ? __('filament.versions.security.clear')
                        : __('filament.versions.security.blockers', ['count' => (int) $state]))
                    ->color(fn (int|string|null $state): string => (int) $state === 0 ? 'success' : 'danger'),
                TextColumn::make('support_status')
                    ->label(__('filament.versions.fields.support_status'))
                    ->badge()
                    ->formatStateUsing(fn (?SupportStatus $state) => $state?->label())
                    ->color(fn (?SupportStatus $state) => $state?->color() ?? 'gray')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('eol_date')
                    ->label(__('filament.versions.fields.eol_date'))
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (Version $record): string => $record->eol_date && $record->eol_date->lte(now()->addDays(90)) ? 'warning' : 'gray')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime('d.m.Y H:i')
                    ->label(__('filament.fields.updated_at'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament.versions.fields.status'))
                    ->options($statusOptions),
                SelectFilter::make('approval_status')
                    ->label(__('filament.versions.fields.approval_status'))
                    ->options($approvalOptions),
                Filter::make('upcoming_eol')
                    ->label(__('filament.versions.filters.upcoming_eol'))
                    ->query(fn ($query) => $query
                        ->where('status', VersionStatus::PUBLISHED->value)
                        ->whereNotNull('eol_date')
                        ->whereDate('eol_date', '>=', now()->toDateString())
                        ->whereDate('eol_date', '<=', now()->addDays(90)->toDateString())),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->label(__('filament.actions.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->url(fn (Version $record) => CreateVersion::getUrl(['duplicate' => $record->getKey()]))
                    ->tooltip(__('filament.actions.duplicate')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
