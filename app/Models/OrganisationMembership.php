<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\Role;

class OrganisationMembership extends Pivot
{
    use HasUuids;

    protected $table = 'organisation_memberships';

    public $incrementing = false; // Key for UUIDs
    protected $keyType = 'string';


    public function isAdmin(): bool
    {
        return $this->role == Role::ADMIN;
    }
}
