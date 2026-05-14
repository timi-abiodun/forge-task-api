<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'email', 'role', 'token', 'status',
        'expires_at',
    ];

    protected $casts = [
        'role' => MembershipRole::class,
        'status'=> InvitationStatus::class,
        'expires_at' => 'datetime',
    ];

    // --- Relationships ---

    // Organisation this invitation was sent from
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }

    // User this invitation was sent by 
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // User this invitation was sent to
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    // Check if the invitation validity period has ended.
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
