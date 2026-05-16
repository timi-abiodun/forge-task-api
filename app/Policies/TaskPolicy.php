<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Traits\ChecksOrganisationRole;


class TaskPolicy
{
    use ChecksOrganisationRole;
    
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $membership = request()->attributes->get('membership');
        return $membership !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // A user can only view a Task if it belongs 
        // to the current organisation context.
        $currentOrg = request()->attributes->get("organisation");

        // Fail-safe check if middleware didn't run or missing context
        if (!$currentOrg) {
            return false;
        }

        return $task->project->organisation_id === $currentOrg->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasAdministrativeAccess();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        if (!$this->view($user, $task)) {
            return false;
        }
        return $this->hasAdministrativeAccess() 
            || $task->assigned_to === $user->id 
            || $task->assigned_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        if (!$this->view($user, $task)) {
            return false;
        }
        return $this->hasAdministrativeAccess() || $task->assigned_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $this->hasAdministrativeAccess() || $task->assigned_by === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $this->hasAdministrativeAccess() || $task->assigned_by === $user->id;
    }
}
