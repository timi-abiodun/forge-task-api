<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendInvitationRequest;
use App\Http\Resources\InvitationResource;
use Illuminate\Http\Request;
use App\Models\Invitation;
use App\Services\InvitationService;
use App\Enums\InvitationStatus;
use Symfony\Component\HttpFoundation\Response;
use App\Mail\OrganisationInvitationMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\MembershipResource;


class InvitationController extends Controller
{

    public function __construct(
        protected InvitationService $invitationService
    ) { }

    /**
     * Display a paginated list of pending invitations for the user's organisation (Admin only).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invitation::class);

        $organisation = request()->attributes->get('organisation');
        $invitations = $this->invitationService->getInvitationList($organisation->id);
        
        return response()->json([
            'invitation' => InvitationResource::collection($invitations),
        ]);
        
    }


    /**
     * Store a new invitation for the current organisation (Admin Only)
     *
     * @param  SendInvitationRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SendInvitationRequest $request)
    {
        $organisation = request()->attributes->get('organisation');
        
        $invitation = $this->invitationService->sendInvitation(
            $organisation, 
            $request->validated(), 
            $request->user()
        );

        return response()->json([
            'invitation' => new InvitationResource($invitation),
        ], Response::HTTP_CREATED);
    }

    /**
     * Revoke a pending invitation (Admin only)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Invitation $invitation)
    {
        $this->authorize("update", $invitation);
        // check if the invitation is still pending
        if ($invitation->status !== InvitationStatus::PENDING) {
            return response()->json(['message' => 'Only pending invitations can be revoked.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $invitation->update(['status' => InvitationStatus::REVOKED]);
        return response()->json(['message' => 'Invitation revoked.'], Response::HTTP_OK);
    }

    /**
     * Resend the email for a pending invitation (Admin only)
     * 
     *  @return \Illuminate\Http\JsonResponse
     */
    public function resend(Invitation $invitation)
    {
        $this->authorize("update", $invitation);

        // check if the invitation is still pending.
        if ($invitation->status !== InvitationStatus::PENDING) {
            return response()->json(['message' => 'Only pending invitations can be resent.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        // Refresh expiration date
        $invitation->update(['expires_at' => now()->addDays(7)]);

        // Resend the Mailable
        Mail::to($invitation->email)->queue(new OrganisationInvitationMail($invitation));

        return response()->json(['message' => 'Invitation sent successfully.'], 200);
    }

    /**
     * Fetch invitation details by token (Public)
     * @return \Illuminate\Http\JsonResponse
     */
    public function retrieve(string $token)
    {
        $invitation = Invitation::with('organisation')->where('token', $token)->firstOrFail();
        
        return response()->json([
            'invitation' => new InvitationResource($invitation),
        ]);
    }

    /**
     * Accept invitation by token (Public)
     * 
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept(string $token)
    {
        $membership = $this->invitationService->acceptInvitation($token);

        return response()->json([
            'membership' => new MembershipResource($membership),
        ]);

    }
}
