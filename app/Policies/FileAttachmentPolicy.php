<?php

namespace App\Policies;

use App\Enums\VersionStatus;
use App\Models\FileAttachment;
use App\Models\User;

class FileAttachmentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, FileAttachment $fileAttachment): bool
    {
        return $this->download($user, $fileAttachment);
    }

    public function create(User $user): bool
    {
        return $user->can('upload_files');
    }

    public function update(User $user, FileAttachment $fileAttachment): bool
    {
        return $user->can('edit_files') || $fileAttachment->version?->software?->created_by === $user->id;
    }

    public function delete(User $user, FileAttachment $fileAttachment): bool
    {
        return $user->can('delete_files') || $fileAttachment->version?->software?->created_by === $user->id;
    }

    public function download(?User $user, FileAttachment $fileAttachment): bool
    {
        $version = $fileAttachment->version;

        if (! $version) {
            return false;
        }

        $isPublished = $version->status === VersionStatus::PUBLISHED;
        $ownsSoftware = $version->software?->created_by && $version->software->created_by === $user?->id;
        $hasPermission = $user?->can('download_files') ?? false;

        return $isPublished || $ownsSoftware || $hasPermission;
    }
}
