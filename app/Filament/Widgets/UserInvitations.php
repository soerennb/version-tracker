<?php

namespace App\Filament\Widgets;

use App\Enums\InvitationStatus;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\InvitationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UserInvitations extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('manage_users') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('filament.users.invitation'))
            ->pluralModelLabel(__('filament.users.invitations'))
            ->heading(__('filament.users.invitations'))
            ->description(__('filament.users.invitations_description'))
            ->query(fn (): Builder => UserInvitation::query()->with('inviter')->latest('created_at'))
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10])
            ->columns([
                TextColumn::make('email')
                    ->label(__('filament.users.email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(__('filament.users.name'))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('filament.users.status.label'))
                    ->state(fn (UserInvitation $record): string => $record->status()->value)
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => InvitationStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => InvitationStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('inviter.name')
                    ->label(__('filament.users.invited_by'))
                    ->placeholder(__('filament.audit.system')),
                TextColumn::make('expires_at')
                    ->label(__('filament.users.expires_at'))
                    ->dateTime('d.m.Y H:i')
                    ->since(),
                TextColumn::make('created_at')
                    ->label(__('filament.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('resendInvitation')
                    ->label(__('filament.actions.resend_invitation'))
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (UserInvitation $record): bool => $record->accepted_at === null)
                    ->requiresConfirmation()
                    ->action(function (UserInvitation $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(InvitationService::class)->resend($actor, $record);

                        Notification::make()
                            ->title(__('filament.users.invitation_resent'))
                            ->success()
                            ->send();
                    }),
                Action::make('revokeInvitation')
                    ->label(__('filament.actions.revoke_invitation'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UserInvitation $record): bool => $record->isUsable())
                    ->requiresConfirmation()
                    ->action(function (UserInvitation $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(InvitationService::class)->revoke($actor, $record);

                        Notification::make()
                            ->title(__('filament.users.invitation_revoked'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
