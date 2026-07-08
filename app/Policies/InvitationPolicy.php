<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use App\Traits\ChecksOrganisationRole;

class InvitationPolicy
{
    use ChecksOrganisationRole;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAdministrativeAccess();
    }

    /**
     * Determine whether the user can create models (Invitations).
     */
    public function create(User $user): bool
    {
        return $this->hasAdministrativeAccess();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        $currentOrg = request()->attributes->get('organisation');

        if (!$currentOrg) {
            return false;
        }

        return $this->hasAdministrativeAccess()
            && $invitation->organisation_id === $currentOrg->id;
    }
}

