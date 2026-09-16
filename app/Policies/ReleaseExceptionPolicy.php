<?php

namespace App\Policies;

use App\Models\ReleaseException;
use App\Models\User;

class ReleaseExceptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_readiness');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReleaseException $releaseException): bool
    {
        return $user->can('view_readiness');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_exceptions');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReleaseException $releaseException): bool
    {
        return $user->can('manage_exceptions');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReleaseException $releaseException): bool
    {
        return $user->can('manage_exceptions');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReleaseException $releaseException): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReleaseException $releaseException): bool
    {
        return false;
    }
}
