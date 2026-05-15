<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        // The Global Scope handles the 'where organisation_id' automatically.
        // The Middleware has already verified the user's membership.
        return response()->json(Project::all());
    }
}
