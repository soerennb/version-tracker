<?php

namespace App\Filament\Resources\Versions\Tables;

use App\Enums\ApprovalStatus;
use App\Enums\VersionStatus;
use App\Filament\Resources\Versions\Pages\CreateVersion;
use App\Models\Version;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('support_status')
                    ->label(__('filament.versions.fields.support_status'))
                    ->wrap()
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
