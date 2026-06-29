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
        $organisation = $request->route('organisation');

        if (!$organisation instanceof Organisation) {
            $organisation = Organisation::findOrFail($organisation);
        }

        // Grab user
        $user = $request->user();

        // Fetch the specific user's membership for THIS organization
        $membership = $user->memberships()->where('organisation_id', $organisation->id)->first();

        // // If no membership record exists, return a 403
        if (!$membership) {
            return response()->json([
                'message' => 'You do not have permission to access this organisation'
            ], Response::HTTP_FORBIDDEN);
        }

        // Inject verified context into request attributes
        $request->attributes->set('organisation', $organisation);
        $request->attributes->set('membership', $membership);
        
        return $next($request);
    }
}
