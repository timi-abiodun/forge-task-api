<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Traits\ChecksOrganisationRole;

class ProjectPolicy
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
    public function view(User $user, Project $project): bool
    {
        // A user can only view a project if it belongs 
        // to the current organisation context.
        $currentOrg = request()->attributes->get("organisation");

        // Fail-safe check if middleware didn't run or missing context
        if (!$currentOrg) {
            return false;
        }

        return $project->organisation_id === $currentOrg->id;
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
    public function update(User $user, Project $project): bool
    {
        return $this->hasAdministrativeAccess() && $this->view($user, $project);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->hasAdministrativeAccess() && $this->view($user, $project);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->hasAdministrativeAccess() && $this->view($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->hasAdministrativeAccess() && $this->view($user, $project);
    }
}
