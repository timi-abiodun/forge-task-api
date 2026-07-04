<?php

namespace App\Http\Middleware;

use App\Models\Organisation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveOrganisationFromSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $organisationId = $request->session()->get('current_organisation_id');

        // No org selected yet this session - default to the user's first membership.
        if (!$organisationId) {
            $firstMembership = $user->memberships()->first();

            if (!$firstMembership) {
                abort(403, 'You do not belong to any organisation.');
            }

            $organisationId = $firstMembership->organisation_id;
            $request->session()->put('current_organisation_id', $organisationId);
        }

        $membership = $user->memberships()->where('organisation_id', $organisationId)->first();

        // Session pointed at an org the user no longer belongs to - fail closed.
        if (!$membership) {
            $request->session()->forget('current_organisation_id');
            abort(403, 'You do not have permission to access this organisation.');
        }

        $organisation = Organisation::findOrFail($organisationId);

        // Same attribute keys the API's SetActiveOrganisation uses, so the
        // BelongsToOrganisation scope and every policy work identically.
        $request->attributes->set('organisation', $organisation);
        $request->attributes->set('membership', $membership);

        return $next($request);
    }
}