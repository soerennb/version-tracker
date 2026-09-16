<?php

namespace App\Filament\Widgets;

use App\Enums\VersionSourceKind;
use App\Models\VersionSource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestGithubMessages extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view_software') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('filament.widgets.github_messages.message'))
            ->pluralModelLabel(__('filament.widgets.github_messages.messages'))
            ->heading(__('filament.widgets.github_messages.heading'))
            ->description(__('filament.widgets.github_messages.description'))
            ->query(fn (): Builder => VersionSource::query()
                ->with(['software', 'version'])
                ->where('provider', 'github')
                ->whereIn('source_kind', [VersionSourceKind::RELEASE->value, VersionSourceKind::TAG->value])
                ->orderByRaw('COALESCE(source_updated_at, last_seen_at, created_at) DESC'))
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10])
            ->columns([
                TextColumn::make('software.name')
                    ->label(__('filament.widgets.github_messages.software'))
                    ->searchable(),
                TextColumn::make('version.version_number')
                    ->label(__('filament.widgets.github_messages.version'))
                    ->searchable(),
                TextColumn::make('source_kind')
                    ->label(__('filament.widgets.github_messages.type'))
                    ->badge()
                    ->formatStateUsing(fn (?VersionSourceKind $state): ?string => $state ? __('filament.widgets.github_messages.types.'.$state->value) : null)
                    ->color(fn (?VersionSourceKind $state): string => $state === VersionSourceKind::RELEASE ? 'info' : 'gray'),
                TextColumn::make('tag_name')
                    ->label(__('filament.widgets.github_messages.tag'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('filament.widgets.github_messages.title'))
                    ->placeholder(__('filament.widgets.github_messages.untitled'))
                    ->wrap(),
                TextColumn::make('is_prerelease')
                    ->label(__('filament.widgets.github_messages.prerelease'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('filament.widgets.github_messages.yes') : __('filament.widgets.github_messages.no'))
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('source_updated_at')
                    ->label(__('filament.widgets.github_messages.updated'))
                    ->state(fn (VersionSource $record) => $record->source_updated_at ?? $record->last_seen_at ?? $record->created_at)
                    ->dateTime('d.m.Y H:i')
                    ->since(),
            ])
            ->recordActions([
                Action::make('openSource')
                    ->label(__('filament.actions.open_source'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (VersionSource $record): ?string => $record->source_url)
                    ->openUrlInNewTab()
                    ->visible(fn (VersionSource $record): bool => filled($record->source_url)),
            ])
            ->toolbarActions([]);
    }
}
