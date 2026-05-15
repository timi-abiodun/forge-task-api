<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Organisation;



class SetActiveOrganisation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Grab the {organisation} route parameter - UUID string
        $orgId = $request->route('organisation');

        // Find the Orgnisation or fail with a 404
        $organisation = Organisation::findOrFail($orgId);

        // Grab user
        $user = $request->user();

        // Check if authenticated user is a Member of this organisation
        $isMember = $user->organisations()->where('organisation_id', $organisation->id)->exists();

        if (!$isMember) {
            // If they aren't a Member, return a 403
            return response()->json([
                'message' => 'You do not have permission to access this organisation'
            ], Response::HTTP_FORBIDDEN);
        }

        // Bind the organisation instance to the request attributes
        $request->attributes->set('organisation', $organisation);
        
        return $next($request);
    }
}
