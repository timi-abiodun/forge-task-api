<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\MembershipRole;

class OrganisationMembership extends Pivot
{
    use HasUuids;

    protected $table = 'organisation_memberships';

    protected $fillable = ['user_id', 'organisation_id', 'role', 'invited_by', 'invited_at'];

    // Crucial for UUID primary keys on pivots
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'invited_at' => 'datetime',
        'role' => MembershipRole::class,
    ];

    public function isAdmin(): bool
    {
        return in_array($this->role, [MembershipRole::OWNER, MembershipRole::ADMIN]);
    }

    // Relationship to the person who issued the invite
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}