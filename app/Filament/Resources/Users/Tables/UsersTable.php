<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserManagementService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.users.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('filament.users.email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label(__('filament.users.role'))
                    ->badge()
                    ->formatStateUsing(fn (?UserRole $state): ?string => $state?->value === null ? null : __('filament.users.roles.'.$state->value))
                    ->color(fn (?UserRole $state): string => match ($state) {
                        UserRole::ADMIN => 'danger',
                        UserRole::EDITOR => 'warning',
                        UserRole::VIEWER => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('is_active')
                    ->label(__('filament.users.active'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('filament.users.status.active') : __('filament.users.status.inactive'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('email_verified_at')
                    ->label(__('filament.users.email_verified'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder(__('filament.users.unverified')),
                TextColumn::make('last_login_at')
                    ->label(__('filament.users.last_login'))
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->placeholder(__('filament.users.never_logged_in'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('filament.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('filament.users.role'))
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => __('filament.users.roles.'.$role->value)])->all()),
                TernaryFilter::make('is_active')
                    ->label(__('filament.users.active')),
                TernaryFilter::make('email_verified_at')
                    ->label(__('filament.users.email_verified'))
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('activateUser')
                    ->label(__('filament.actions.activate_user'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (User $record): bool => ! $record->isActive())
                    ->action(function (User $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(UserManagementService::class)->setActive($actor, $record, true);

                        Notification::make()
                            ->title(__('filament.users.activated'))
                            ->success()
                            ->send();
                    }),
                Action::make('deactivateUser')
                    ->label(__('filament.actions.deactivate_user'))
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->authorize('update')
                    ->visible(fn (User $record): bool => $record->isActive()
                        && auth()->user() instanceof User
                        && app(UserManagementService::class)->canChangeActiveState(auth()->user(), $record, false))
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(UserManagementService::class)->setActive($actor, $record, false);

                        Notification::make()
                            ->title(__('filament.users.deactivated'))
                            ->success()
                            ->send();
                    }),
                Action::make('verifyEmail')
                    ->label(__('filament.actions.verify_email'))
                    ->icon('heroicon-o-check-badge')
                    ->authorize('update')
                    ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
                    ->action(function (User $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(UserManagementService::class)->verifyEmail($actor, $record);

                        Notification::make()
                            ->title(__('filament.users.email_verified_success'))
                            ->success()
                            ->send();
                    }),
                Action::make('resendVerification')
                    ->label(__('filament.actions.resend_verification'))
                    ->icon('heroicon-o-paper-airplane')
                    ->authorize('update')
                    ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
                    ->action(function (User $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(UserManagementService::class)->resendVerification($actor, $record);

                        Notification::make()
                            ->title(__('filament.users.verification_sent'))
                            ->success()
                            ->send();
                    }),
                Action::make('sendPasswordReset')
                    ->label(__('filament.actions.send_password_reset'))
                    ->icon('heroicon-o-key')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(UserManagementService::class)->sendPasswordReset($actor, $record);

                        Notification::make()
                            ->title(__('filament.users.password_reset_sent'))
                            ->success()
                            ->send();
                    }),
                Action::make('revokeTokens')
                    ->label(__('filament.actions.revoke_tokens'))
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        $count = app(UserManagementService::class)->revokeTokens($actor, $record);

                        Notification::make()
                            ->title(__('filament.users.tokens_revoked'))
                            ->body(__('filament.users.tokens_revoked_description', ['count' => $count]))
                            ->success()
                            ->send();
                    }),
                Action::make('deleteUser')
                    ->label(__('filament.actions.delete_user'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->authorize('delete')
                    ->visible(fn (User $record): bool => auth()->user() instanceof User
                        && app(UserManagementService::class)->canDelete(auth()->user(), $record))
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);
                        app(UserManagementService::class)->delete($actor, $record);

                        Notification::make()
                            ->title(__('filament.users.deleted'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
