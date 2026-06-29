<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;
use App\Traits\ChecksOrganisationRole;
use App\Enums\MembershipRole;

class OrganisationPolicy
{
    use ChecksOrganisationRole;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->organisations()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organisation $organisation): bool
    {
        // Check if the user has a membership in the organisation
        return $organisation->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organisation $organisation): bool
    {
        $membership = $organisation->memberships()
            ->where('user_id', $user->id)
            ->first();

        return $this->hasAdministrativeAccess($membership);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organisation $organisation): bool
    {
        $membership = $organisation->memberships()
            ->where('user_id', $user->id)
            ->first();

        // Only allow deletion if the user is the owner of the organisation
        return $membership !== null
            && $membership->role === MembershipRole::OWNER;
    }
}