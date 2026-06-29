<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\MembershipRole;

class OrganisationController extends Controller
{
    /**
     *  Store a newly created organisation in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $membership = DB::transaction(function () use ($data) {
            $organisation = Organisation::create($data);

            return $organisation->memberships()->create([
                'user_id' => auth()->id(),
                'role' => MembershipRole::OWNER,
            ]);
        });

        return response()->json($membership, Response::HTTP_CREATED);
    }

    /**
     * Display a listing of the organisations.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Organisation::class);

        // Paginate the organisations for the authenticated user
        $organisations = auth()->user()->organisations()->paginate(15);

        return response()->json($organisations);
    }

    /**
     * Display the specified organisation.
     */
    public function show(Organisation $organisation): JsonResponse
    {
        $this->authorize('view', $organisation);
        return response()->json($organisation, Response::HTTP_OK);
    }

    /**
     * Update the specified organisation.
     */
    public function update(Request $request, Organisation $organisation): JsonResponse
    {
        $this->authorize('update', $organisation);
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $organisation->update($data);

        return response()->json($organisation, Response::HTTP_OK);
    }

    /**
     * Remove the specified organisation.
     */
    public function destroy(Organisation $organisation): JsonResponse
    {
        $this->authorize('delete', $organisation);
        $organisation->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}