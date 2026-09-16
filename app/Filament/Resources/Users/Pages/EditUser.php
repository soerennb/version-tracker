<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User && $record instanceof User, 403);

        return app(UserManagementService::class)->update($actor, $record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('filament.users.updated');
    }
}
