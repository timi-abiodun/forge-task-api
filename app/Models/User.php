<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasUuids, HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'first_name', 'last_name',
        'email', 'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // --- Relationships ---

    /**
     * Organisations this User belongs to
     * @return BelongsToMany<Organisation, User, OrganisationMembership>
     */
    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(Organisation::class, 'organisation_memberships')
                    ->using(OrganisationMembership::class) // The "Custom" link
                    ->withPivot('role', 'invited_by', 'invited_at')
                    ->withTimestamps();
    }

    /**
     * Tasks this user has assigned to others
     * @return HasMany<Task, User>
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    /**
     * Tasks this user has been assigned
     * @return HasMany<Task, User>
     */
    public function receivedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Invitations this user accepted
     * @return HasMany<Invitation, User>
     */
    public function acceptedInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'accepted_by');
    }

    /**
     * Invitations this user sent to others
     * @return HasMany<Invitation, User>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * Summary of attachments under User
     * @return HasMany<Attachment, User>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    /**
     * Memberships under the User
     * @return HasMany<Attachment, User>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganisationMembership::class, 'user_id');
    }
}