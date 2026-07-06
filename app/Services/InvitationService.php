<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\Organisation;
use App\Enums\InvitationStatus;
use App\Models\User;
use App\Mail\OrganisationInvitationMail; 
use Illuminate\Support\Facades\Mail;    
use Illuminate\Validation\ValidationException; 
use App\Models\OrganisationMembership;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class InvitationService
{
    /**
     * Create a new organization invitation and queue the notification email.
     *
     * @param  Organisation  $organisation  The organization the user is being invited to.
     * @param  array{email: string, role: string}  $data  The validated invitation payload containing 'email' and 'role'.
     * @param  User  $invitedBy  The user generating the invitation.
     * @return Invitation
     *
     * @throws ValidationException If the user is already a member or has a pending invitation.
     */
    
    public function sendInvitation(Organisation $organisation, array $data, User $invitedBy): Invitation
    {
        return DB::transaction(function () use ($organisation, $data, $invitedBy) {
            // Check if already member
            $isMember = $organisation->users()->where('email', $data['email'])->exists();

            if ($isMember) {
                throw ValidationException::withMessages([
                    'user'=> ['User is already a member of this organisation'],
                ]);
            }

            // Check for pending invitations
            $activeExists = Invitation::where('organisation_id', $organisation->id)
                ->where('email', $data['email'])
                ->where('status', InvitationStatus::PENDING->value)
                ->where('expires_at', '>', now())
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'invitation'=> ['An active invitation already exists for this email.'],
                ]);
            }

            // Create the invitation if checks pass
            $invitation = Invitation::create([
                'organisation_id' => $organisation->id,
                'invited_by'      => $invitedBy->id,
                'email'           => $data['email'],
                'role'            => $data['role'],
                'token'           => bin2hex(random_bytes(32)),
                'expires_at'      => now()->addDays(7),
                ]);;
            
            // Fire off the email
            Mail::to($invitation->email)->queue(new OrganisationInvitationMail($invitation));

            return $invitation;

        });
        
    }



    /**
     * Accept an organisation invitation using a unique token.
     *
     * This method validates the invitation token, verifies that the invitation is 
     * pending and has not expired, and ensures the target user exists (creating a 
     * placeholder user profile if they do not). Finally, it establishes the 
     * organisation membership and updates the invitation status within a database transaction.
     *
     * @param string $token The unique security token associated with the invitation.
     * @return OrganisationMembership The newly created membership instance.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *         If the token does not match any invitation.
     *
     * @throws ValidationException
     *         If the invitation has already been processed or expired.
     */

    public function acceptInvitation(string $token): OrganisationMembership
    {
        return DB::transaction(function () use ($token) {
            // Find the invitaion by token or 404 if not found
            $invitation = Invitation::where('token', $token)->firstOrFail();
            
            // Check if it's still pending or expired
            if ($invitation->status !== InvitationStatus::PENDING) {
                throw ValidationException::withMessages([
                    'invitation' => ['This invitation has already been processed.'],
                ]);
            }

            if ($invitation->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'invitation'=> ['This invitation has expired.'],
                ]);
            
            }

            // Find or Create the User by email
            $user = User::where('email', $invitation->email)->first();

            if (!$user) {
                $user = User::create([
                    'email'      => $invitation->email,
                    'first_name' => 'Invited', // Fallback placeholder
                    'last_name'  => 'User',    // Fallback placeholder
                    'password'   => bcrypt(Str::random(16)), // Temporary password
                ]);
            }

            // Create OrganisationMembership table
            $membership = OrganisationMembership::create([
                'user_id' => $user->id,
                'organisation_id' => $invitation->organisation_id,
                'role' => $invitation->role,
                'invited_by' => $invitation->invited_by,
            ]);

            // Mark the invitation as accepted
            $invitation->update([
                'status' => InvitationStatus::ACCEPTED->value,
                'accepted_by' => $user->id,
                'accepted_at' => now(),
            ]);

            // Return the membership data
            return $membership;
        });
        
    }

    /**
     * Retrieve a paginated list of pending invitations for a specific organisation.
     *
     * @param string $organisationId The UUID of the organisation.
     * @return \Illuminate\Pagination\LengthAwarePaginator<Invitation>
     */
    public function getInvitationList(string $organisationId): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Invitation::query()
            ->with(['organisation:id,name'])
            ->where('organisation_id', $organisationId)
            ->where('status', InvitationStatus::PENDING)
            ->latest()
            ->paginate(10);
    }



    /**
     * Accept an invitation and prepare password setup for newly created users.
     *
     * This does not replace acceptInvitation(); the API path remains unchanged.
     * The web layer uses this method so new-user password setup can happen without
     * touching the existing tested logic.
     *
     * @param string $token The unique invitation token.
     * @return array{membership: OrganisationMembership, user: User, is_new_user: bool, reset_token: ?string}
     */

    public function acceptInvitationWithPasswordSetup(string $token): array
    {
        $wasNewUser = false;
        $acceptedUser = null;

        $membership = DB::transaction(function () use ($token, &$wasNewUser, &$acceptedUser) {
            $invitation = Invitation::where('token', $token)->firstOrFail();

            if ($invitation->status !== InvitationStatus::PENDING) {
                throw ValidationException::withMessages([
                    'invitation' => ['This invitation has already been processed.'],
                ]);
            }

            if ($invitation->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'invitation' => ['This invitation has expired.'],
                ]);
            }

            $user = User::where('email', $invitation->email)->first();

            if (!$user) {
                $wasNewUser = true;
                $user = User::create([
                    'email' => $invitation->email,
                    'first_name' => 'Invited',
                    'last_name' => 'User',
                    'password' => bcrypt(Str::random(32)),
                ]);
            }

            $acceptedUser = $user;

            $membership = OrganisationMembership::create([
                'user_id' => $user->id,
                'organisation_id' => $invitation->organisation_id,
                'role' => $invitation->role,
                'invited_by' => $invitation->invited_by,
            ]);

            $invitation->update([
                'status' => InvitationStatus::ACCEPTED->value,
                'accepted_by' => $user->id,
                'accepted_at' => now(),
            ]);

            return $membership;
        });

        $resetToken = $wasNewUser
            ? app('auth.password.broker')->createToken($acceptedUser)
            : null;

        return [
            'membership' => $membership,
            'user' => $acceptedUser,
            'is_new_user' => $wasNewUser,
            'reset_token' => $resetToken,
        ];
    }

}