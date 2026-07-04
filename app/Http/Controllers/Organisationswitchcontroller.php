<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrganisationSwitchController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'organisation_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();

        $belongs = $user->memberships()
            ->where('organisation_id', $request->input('organisation_id'))
            ->exists();

        if (!$belongs) {
            abort(403, 'You do not have permission to access this organisation.');
        }

        $request->session()->put('current_organisation_id', $request->input('organisation_id'));

        return redirect()->back();
    }
}