<?php

use App\Events\TaskCreated;
use App\Listeners\SendTaskAssignedNotification;
use App\Listeners\SendTaskCreatedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('task creation notifies both creator and assignee when they are different users', function () {
    Notification::fake();

    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assigner->id,
        'assigned_to' => $assignee->id,
    ]);

    $listener = new SendTaskCreatedNotification();
    $listener->handle(new TaskCreated($task, $assigner));

    Notification::assertSentTo($assigner, TaskAssigned::class);
    Notification::assertSentTo($assignee, TaskAssigned::class);
    Notification::assertCount(2);
});

test('task creation sends only one notification when creator assigns task to self', function () {
    Notification::fake();

    $assigner = User::factory()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assigner->id,
        'assigned_to' => $assigner->id,
    ]);

    $listener = new SendTaskCreatedNotification();
    $listener->handle(new TaskCreated($task, $assigner));

    Notification::assertSentTo($assigner, TaskAssigned::class);
    Notification::assertCount(1);
});

test('task creation with no assignee still notifies creator only', function () {
    Notification::fake();

    $assigner = User::factory()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assigner->id,
        'assigned_to' => null,
    ]);

    $listener = new SendTaskCreatedNotification();
    $listener->handle(new TaskCreated($task, $assigner));

    Notification::assertSentTo($assigner, TaskAssigned::class);
    Notification::assertCount(1);
});

test('task created notification uses mail and database channels with correct payload', function () {
    Notification::fake();

    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assigner->id,
        'assigned_to' => $assignee->id,
    ]);

    $listener = new SendTaskCreatedNotification();
    $listener->handle(new TaskCreated($task, $assigner));

    Notification::assertSentTo(
        $assignee,
        TaskAssigned::class,
        function (TaskAssigned $notification, array $channels) use ($assigner, $assignee, $task, $project) {
            $payload = $notification->toDatabase($assignee);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $payload['task_id'] === $task->id
                && $payload['task_name'] === $task->name
                && $payload['project_id'] === $project->id
                && $payload['assigned_by'] === $assigner->id
                && $payload['organisation_id'] === $project->organisation_id;
        }
    );
});

test('task created notification builds successfully even when project relation is not preloaded', function () {
    Notification::fake();

    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assigner->id,
        'assigned_to' => $assignee->id,
    ]);

    $task->unsetRelation('project');
    $task->unsetRelation('assignee');

    $listener = new SendTaskCreatedNotification();
    $listener->handle(new TaskCreated($task, $assigner));

    Notification::assertSentTo(
        $assignee,
        TaskAssigned::class,
        function (TaskAssigned $notification) use ($assignee) {
            $notification->toDatabase($assignee);
            $notification->toMail($assignee);

            return true;
        }
    );
});

test('task notification listeners are queued', function () {
    expect(is_subclass_of(SendTaskCreatedNotification::class, ShouldQueue::class))->toBeTrue();
    expect(is_subclass_of(SendTaskAssignedNotification::class, ShouldQueue::class))->toBeTrue();
    expect(is_subclass_of(TaskAssigned::class, ShouldQueue::class))->toBeTrue();
});

test('dispatching task created twice remains idempotent for notifications', function () {
    Notification::fake();

    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assigner->id,
        'assigned_to' => $assignee->id,
    ]);

    $listener = new SendTaskCreatedNotification();

    $listener->handle(new TaskCreated($task, $assigner));
    $listener->handle(new TaskCreated($task, $assigner));

    expect(Notification::sent($assigner, TaskAssigned::class))->toHaveCount(1);
    expect(Notification::sent($assignee, TaskAssigned::class))->toHaveCount(1);
    Notification::assertCount(2);
});

test('task created notification uses the event assigner identity in payload', function () {
    Notification::fake();

    $taskOwner = User::factory()->create();
    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $taskOwner->id,
        'assigned_to' => $assignee->id,
    ]);

    $listener = new SendTaskCreatedNotification();
    $listener->handle(new TaskCreated($task, $assigner));

    Notification::assertSentTo(
        $assignee,
        TaskAssigned::class,
        function (TaskAssigned $notification) use ($assigner, $assignee) {
            $payload = $notification->toDatabase($assignee);

            return $payload['assigned_by'] === $assigner->id;
        }
    );
});
