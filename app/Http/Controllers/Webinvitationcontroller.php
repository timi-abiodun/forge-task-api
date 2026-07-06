<?php

namespace App\Http\Controllers;

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use App\Mail\OrganisationInvitationMail;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class WebInvitationController extends Controller
{
    public function __construct(protected InvitationService $invitationService) {}

    // --- Admin-side management (auth + active_org) ---

    public function index()
    {
        $this->authorize('viewAny', Invitation::class);

        $organisation = request()->attributes->get('organisation');
        $invitations = $this->invitationService->getInvitationList($organisation->id);

        return view('invitations.index', compact('invitations'));
    }

    public function create()
    {
        $this->authorize('create', Invitation::class);

        return view('invitations.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Invitation::class);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(MembershipRole::class)],
        ]);

        $organisation = request()->attributes->get('organisation');

        $this->invitationService->sendInvitation($organisation, $data, auth()->user());

        return redirect()->route('invitations.index')->with('status', 'Invitation sent.');
    }

    public function destroy(Invitation $invitation)
    {
        $this->authorize('update', $invitation);

        if ($invitation->status !== InvitationStatus::PENDING) {
            return back()->withErrors(['invitation' => 'Only pending invitations can be revoked.']);
        }

        $invitation->update(['status' => InvitationStatus::REVOKED]);

        return redirect()->route('invitations.index')->with('status', 'Invitation revoked.');
    }

    public function resend(Invitation $invitation)
    {
        $this->authorize('update', $invitation);

        if ($invitation->status !== InvitationStatus::PENDING) {
            return back()->withErrors(['invitation' => 'Only pending invitations can be resent.']);
        }

        $invitation->update(['expires_at' => now()->addDays(7)]);
        Mail::to($invitation->email)->queue(new OrganisationInvitationMail($invitation));

        return redirect()->route('invitations.index')->with('status', 'Invitation resent.');
    }

    // --- Public: invitee-facing (no auth/active_org middleware) ---

    public function show(string $token)
    {
        $invitation = Invitation::with('organisation')->where('token', $token)->firstOrFail();

        return view('invitations.show', compact('invitation', 'token'));
    }

    public function accept(string $token)
    {
        $result = $this->invitationService->acceptInvitationWithPasswordSetup($token);

        if ($result['is_new_user']) {
            return redirect()->route('password.set', [
                'token' => $result['reset_token'],
                'email' => $result['user']->email,
            ]);
        }

        return redirect()->route('login')
            ->with('status', 'Invitation accepted! Log in to access the organisation.');
    }

    public function reject(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        abort_if(
            $invitation->status !== InvitationStatus::PENDING,
            422,
            'This invitation has already been processed.'
        );

        $invitation->update(['status' => InvitationStatus::DECLINED]);

        return view('invitations.rejected');
    }

    // --- New-account password setup, uses Laravel's built-in password broker ---

    public function showSetPassword(Request $request)
    {
        return view('invitations.set-password', [
            'token' => $request->query('token'),
            'email' => $request->query('email'),
        ]);
    }

    public function submitSetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            Auth::login($user);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect()->route('dashboard')->with('status', 'Welcome! Your password is set.');
    }
}