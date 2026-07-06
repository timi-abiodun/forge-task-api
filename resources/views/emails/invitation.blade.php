<x-mail::message>
# You're Invited!

You have been invited by **{{ $invitation->sender->fullName }}** to join the organization **{{ $invitation->organisation->name }}**.

### Your Assigned Role
You have been assigned the role of: **{{ $invitation->role->value }}**.

<x-mail::button :url="route('invitations.public.show', ['token' => $invitation->token])">
Accept Invitation
</x-mail::button>

**Note:** This invitation will expire on **{{ $invitation->expires_at->toFormattedDateString() }}**. Please ensure you accept before this time to gain access to the organization.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>