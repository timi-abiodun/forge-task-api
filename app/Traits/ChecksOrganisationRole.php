<?php
namespace App\Traits;

use App\Enums\MembershipRole;

trait ChecksOrganisationRole
{
    protected function hasAdministrativeAccess(): bool
    {
        $membership = request()->attributes->get('membership');
        if (!$membership) return false;
        
        return in_array($membership->role, [
            MembershipRole::ADMIN,
            MembershipRole::OWNER,
        ], true);
    }
}