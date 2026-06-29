<?php

namespace App\Http\Controllers;

use App\Events\TaskCreated;
use App\Events\TaskReassigned;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Http\Requests\TaskRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    /**
     * Display a paginated listing of the organization's Tasks.
     */
    public function index(): JsonResponse
{
    $this->authorize('viewAny', Task::class);
    
    $organisation = request()->attributes->get('organisation');
    
    $tasks = Task::whereHas('project', fn($q) => 
        $q->where('organisation_id', $organisation->id)
    )->paginate(15);
    
    return response()->json($tasks);
}

    /**
     * Store a newly created Task in storage.
     */
    public function store(TaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $task = Task::create($data);

        TaskCreated::dispatch($task, $request->user());
        
        return response()->json($task, Response::HTTP_CREATED);
    }

    /**
     * Display the specified Task.
     */
    public function show($organisation, Task $task): JsonResponse
    {
        // Instantly checks the 'view' method in the TaskPolicy
        $this->authorize("view", $task);

        return response()->json($task, Response::HTTP_OK);
    }

    /**
     * Update the specified Task.
     */
    public function update(UpdateTaskRequest $request, $organisation, Task $task): JsonResponse
    {
        $data = $request->validated();
        $previousAssignedTo = $task->assigned_to;

        $task->update($data);

        // Fire TaskReassigned only if assigned_to changed
        if ($task->assigned_to !== $previousAssignedTo) {
            TaskReassigned::dispatch($task, $request->user());
        }

        return response()->json($task, Response::HTTP_OK);
    }

    /**
     * Delete the specified Task.
     */
    public function destroy($organisation, Task $task): JsonResponse
    {
        // Instantly checks the 'delete' method in the TaskPolicy
        $this->authorize("delete", $task);

        $task->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
