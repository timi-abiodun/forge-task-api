<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = ['name'];

    // --- Relationships ---

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organisation_memberships')
                    ->using(OrganisationMembership::class) // The "Custom" link
                    ->withPivot('role', 'invited_by', 'invited_at')
                    ->withTimestamps();
    }

    // Projects created under organisation
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'organisation_id');
    }

    // Invitations sent under Organisation
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class,'organisation_id');
    }

    // Memberships under the Organisation
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganisationMembership::class, 'organisation_id');
    }
}
