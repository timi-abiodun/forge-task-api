<?php

namespace App\Http\Controllers;

use App\Enums\MembershipRole;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebOrganisationController extends Controller
{
    public function create()
    {
        return view('organisations.create');
    }

    public function store(Request $request)
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

        // Auto-switch to the new org so it's immediately usable for testing.
        $request->session()->put('current_organisation_id', $membership->organisation_id);

        return redirect()->route('projects.index')->with('status', 'Organisation created.');
    }
}