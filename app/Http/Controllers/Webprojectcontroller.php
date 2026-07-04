<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;

class WebProjectController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Project::class);

        $organisation = request()->attributes->get('organisation');

        $projects = Project::where('organisation_id', $organisation->id)
            ->latest()
            ->paginate(10);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(ProjectRequest $request)
    {
        $data = $request->validated();

        // organisation_id is set automatically by BelongsToOrganisation's
        // creating() hook, reading the same request attribute this
        // controller's middleware sets. No need to pass it explicitly.
        $project = Project::create($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        // NOTE: assumes Task has a `title` field - I haven't seen the Task
        // model yet. Adjust if this errors.
        $tasks = $project->tasks()->latest()->get();

        return view('projects.show', compact('project', 'tasks'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $data = $request->validated();

        $project->update($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }
}