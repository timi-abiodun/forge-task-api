<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
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
        $currentOrg = request()->attributes->get('organisation');

        if (! $currentOrg) {
            return false;
        }

        // Bypass tenant scopes to verify the project's actual organisation.
        $project = Project::withoutGlobalScopes()->find($attachment->task->project_id);

        return $project && $project->organisation_id === $currentOrg->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Task $task): bool
    {
        $currentOrg = request()->attributes->get('organisation');

        if (! $currentOrg) {
            return false;
        }

        // Load project without tenant scopes to avoid null when task belongs to another org.
        $project = Project::withoutGlobalScopes()->find($task->project_id);

        // Deny access if the task has no project or belongs to a different
        // organisation than the active request context.
        if (! $project || $project->organisation_id !== $currentOrg->id) {
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
