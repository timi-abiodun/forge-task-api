<?php
namespace App\Traits;

use App\Enums\MembershipRole;
use App\Models\OrganisationMembership;

trait ChecksOrganisationRole
{
    /**
         * Determine if there is administrative access for the given or current context.
         *
         * @param OrganisationMembership|null $membership Optional Organisationalmembership model. If null, falls back to the HTTP request attribute.
         * @return bool
         */
    protected function hasAdministrativeAccess(?OrganisationMembership $membership = null): bool
    {
        // Fallback to request ONLY if nothing was passed.
        $membership = $membership ?? request()->attributes->get('membership');
        if (!$membership){
            return false;
        }

        return in_array($membership->role, [
            MembershipRole::ADMIN,
            MembershipRole::OWNER,
        ], true);
    }
}