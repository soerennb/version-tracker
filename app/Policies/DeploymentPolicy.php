<?php

namespace App\Policies;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\User;

class DeploymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_deployments');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deployment $deployment): bool
    {
        return $user->can('view_deployments');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_deployments');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deployment $deployment): bool
    {
        return $user->can('create_deployments') && $deployment->status === DeploymentStatus::PLANNED;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Deployment $deployment): bool
    {
        return false;
    }

    public function approve(User $user, Deployment $deployment): bool
    {
        return $user->can('approve_deployments') && $deployment->status === DeploymentStatus::PLANNED;
    }

    public function start(User $user, Deployment $deployment): bool
    {
        return $user->can('execute_deployments') && $deployment->status === DeploymentStatus::APPROVED;
    }

    public function complete(User $user, Deployment $deployment): bool
    {
        return $user->can('execute_deployments') && $deployment->status === DeploymentStatus::IN_PROGRESS;
    }

    public function cancel(User $user, Deployment $deployment): bool
    {
        return $user->can('execute_deployments') && $deployment->status?->isActive();
    }

    public function rollback(User $user, Deployment $deployment): bool
    {
        return $user->can('execute_deployments') && $deployment->status === DeploymentStatus::SUCCEEDED;
    }

    public function correct(User $user, Deployment $deployment): bool
    {
        return $user->can('correct_deployments') && $deployment->status?->isTerminal();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Deployment $deployment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Deployment $deployment): bool
    {
        return false;
    }
}
