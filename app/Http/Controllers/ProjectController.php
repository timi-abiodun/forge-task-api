<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Http\Requests\ProjectRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    /**
     * Display a paginated listing of the organization's projects.
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('viewAny', $project);
        // Global scope applies, but paginate() prevents memory crashes
        return response()->json(Project::paginate(15));
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $project = Project::create($data);
        
        return response()->json($project, Response::HTTP_CREATED);
    }

    /**
     * Display the specified project.
     */
    public function show($organisation, Project $project): JsonResponse
    {
        // Instantly checks the 'view' method in the ProjectPolicy
        $this->authorize("view", $project);

        return response()->json($project, Response::HTTP_OK);
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();
        $project->update($data);

        return response()->json($project, Response::HTTP_OK);
    }

    /**
     * Delete the specified project.
     */
    public function destroy($organisation, Project $project): JsonResponse
    {
        // Instantly checks the 'delete' method in the ProjectPolicy
        $this->authorize("delete", $project);

        $project->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}