<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use App\Models\Task;
use App\Traits\ChecksOrganisationRole;

class AttachmentPolicy
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
    public function view(User $user, Attachment $attachment): bool
    {
        // A user can only view a Attachment if it belongs 
        // to the current organisation context.
        $currentOrg = request()->attributes->get("organisation");

        // Fail-safe check if middleware didn't run or missing context
        if (!$currentOrg) {
            return false;
        }

        return $attachment->task->project->organisation_id === $currentOrg->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Task $task): bool
    {
        $currentOrg = request()->attributes->get("organisation");
        if (!$currentOrg || $task->project->organisation_id !== $currentOrg->id) { 
            return false;
        }
        return $this->hasAdministrativeAccess() 
            || $task->assigned_to === $user->id 
            || $task->assigned_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
       return $this->view($user, $attachment) 
            && ($this->hasAdministrativeAccess() || $attachment->uploaded_by === $user->id);
    }
}
