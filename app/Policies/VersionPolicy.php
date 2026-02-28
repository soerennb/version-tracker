<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Models\User;
use App\Models\Version;

class VersionPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->can('view_versions') ?? false;
    }

    public function view(?User $user, Version $version): bool
    {
        return $user?->can('view_versions') ?? false;
    }

    public function create(User $user): bool
    {
        return $user->can('create_versions');
    }

    public function update(User $user, Version $version): bool
    {
        return $user->can('edit_versions') || $version->software?->created_by === $user->id;
    }

    public function delete(User $user, Version $version): bool
    {
        $isApproved = $version->approval_status === ApprovalStatus::APPROVED;
        $hasVulnerabilities = $version->vulnerabilities()->exists();

        return $user->can('delete_versions') && ! $isApproved && ! $hasVulnerabilities;
    }

    public function approve(User $user, Version $version): bool
    {
        return $user->can('approve_versions') && $version->approval_status === ApprovalStatus::PENDING;
    }

    public function publish(User $user, Version $version): bool
    {
        return $user->can('publish_versions') && $version->approval_status === ApprovalStatus::APPROVED;
    }

    public function restore(User $user, Version $version): bool
    {
        return $user->can('delete_versions');
    }

    public function forceDelete(User $user, Version $version): bool
    {
        return $user->can('delete_versions');
    }
}
