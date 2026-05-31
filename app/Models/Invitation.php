<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Invitation
 *
 * Represents an invitation sent to a user to join an organisation.
 *
 * @property string $id
 * @property string $organisation_id
 * @property string $invited_by
 * @property string|null $accepted_by
 * @property string $email
 * @property MembershipRole $role The role assigned to the user upon acceptance.
 * @property string $token Unique identifier for the invitation link.
 * @property InvitationStatus $status Current status of the invitation.
 * @property Carbon $expires_at The timestamp when the invitation expires.
 * @property Carbon|null $accepted_at The timestamp when the invitation was accepted.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Organisation $organisation The organisation associated with this invitation.
 * @property-read User $sender The user who created the invitation.
 * @property-read User|null $acceptedBy The user who accepted the invitation.
 */
class Invitation extends Model
{
    use HasUuids, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email', 
        'role', 
        'token', 
        'status', 
        'expires_at',
        'organisation_id',
        'invited_by',
        'accepted_by',
        'accepted_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => MembershipRole::class,
        'status' => InvitationStatus::class,
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // --- Relationships ---

    /**
     * Get the organisation this invitation belongs to.
     *
     * @return BelongsTo<Organisation, Invitation>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }

    /**
     * Get the user who sent this invitation.
     *
     * @return BelongsTo<User, Invitation>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who accepted this invitation.
     *
     * @return BelongsTo<User, Invitation>
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Determine if the invitation validity period has passed.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}