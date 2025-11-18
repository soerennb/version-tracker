<?php

namespace App\Policies;

use App\Models\Software;
use App\Models\User;

class SoftwarePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Software $software): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create_software');
    }

    public function update(User $user, Software $software): bool
    {
        return $user->can('edit_software') || $software->created_by === $user->id;
    }

    public function delete(User $user, Software $software): bool
    {
        return $user->can('delete_software') && ! $software->versions()->exists();
    }

    public function manageDependencies(User $user, Software $software): bool
    {
        return $user->can('manage_dependencies') || $software->created_by === $user->id;
    }

    public function restore(User $user, Software $software): bool
    {
        return $user->can('delete_software');
    }

    public function forceDelete(User $user, Software $software): bool
    {
        return $user->can('delete_software');
    }
}
