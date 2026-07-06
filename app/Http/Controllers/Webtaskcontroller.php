<?php

namespace App\Http\Controllers;

use App\Enums\MembershipRole;
use App\Events\TaskCreated;
use App\Events\TaskReassigned;
use App\Http\Requests\TaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;

class WebTaskController extends Controller
{
    public function create(Project $project)
    {
        $this->authorize('create', Task::class);

        $members = $this->orgMembers();

        return view('tasks.create', compact('project', 'members'));
    }

    public function store(TaskRequest $request, Project $project)
    {
        $data = $request->validated();
        $data['assigned_by'] = auth()->id();

        $task = Task::create($data);

        TaskCreated::dispatch($task, auth()->user());

        return redirect()->route('tasks.show', $task)->with('status', 'Task created.');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        // NOTE: heuristic, not verified against hasAdministrativeAccess() -
        // I haven't seen that trait. Assumes only MembershipRole::OWNER
        // counts as admin. Server-side UpdateTaskRequest enforces the real
        // rule regardless, so worst case here is a UI mismatch, not a
        // security gap. Verify before trusting this in a demo.
        $membership = request()->attributes->get('membership');
        $isAdmin = $membership->role === MembershipRole::OWNER;
        $isAssigner = $task->assigned_by === auth()->id();
        $restricted = !$isAdmin && !$isAssigner && $task->assigned_to === auth()->id();

        return view('tasks.edit', compact('task', 'restricted'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();
        $previousAssignedTo = $task->assigned_to;

        $task->update($data);

        // NOTE: UpdateTaskRequest's rules() never actually includes
        // assigned_to in either branch, so this never fires via this
        // endpoint today - flagging, not silently "fixing" your API.
        if (array_key_exists('assigned_to', $data) && $task->assigned_to !== $previousAssignedTo) {
            TaskReassigned::dispatch($task, auth()->user());
        }

        return redirect()->route('tasks.show', $task)->with('status', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $projectId = $task->project_id;
        $task->delete();

        return redirect()->route('projects.show', $projectId)->with('status', 'Task deleted.');
    }

    private function orgMembers()
    {
        $organisation = request()->attributes->get('organisation');

        return \App\Models\User::whereHas('organisations', function ($q) use ($organisation) {
            $q->where('organisations.id', $organisation->id);
        })->get();
    }
}