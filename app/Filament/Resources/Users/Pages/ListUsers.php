<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\UserInvitations;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\RuntimeSettings;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inviteUser')
                ->label(__('filament.actions.invite_user'))
                ->icon('heroicon-o-envelope')
                ->schema([
                    TextInput::make('email')
                        ->label(__('filament.users.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(User::class, 'email'),
                    TextInput::make('name')
                        ->label(__('filament.users.name'))
                        ->maxLength(255),
                ])
                ->visible(fn (): bool => (auth()->user()?->can('manage_users') ?? false)
                    && app(RuntimeSettings::class)->invitationRegistrationAllowed())
                ->action(function (array $data): void {
                    $actor = auth()->user();

                    abort_unless($actor instanceof User, 403);

                    app(InvitationService::class)->create($actor, $data);

                    Notification::make()
                        ->title(__('filament.users.invitation_created'))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [UserInvitations::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
