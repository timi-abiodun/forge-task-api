<?php

use App\Events\TaskReassigned;
use App\Listeners\SendTaskAssignedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Support\Facades\Notification;

test('task reassignment notifies the new assignee with the correct assigner payload', function () {
	Notification::fake();

	$assigner = User::factory()->create();
	$assignee = User::factory()->create();
	$project = Project::factory()->create();

	$task = Task::factory()->create([
		'project_id' => $project->id,
		'assigned_by' => $assigner->id,
		'assigned_to' => $assignee->id,
	]);

	$listener = new SendTaskAssignedNotification();
	$listener->handle(new TaskReassigned($task, $assigner));

	Notification::assertSentTo(
		$assignee,
		TaskAssigned::class,
		function (TaskAssigned $notification, array $channels) use ($assigner, $assignee, $task) {
			$payload = $notification->toDatabase($assignee);

			return in_array('mail', $channels, true)
				&& in_array('database', $channels, true)
				&& $payload['task_id'] === $task->id
				&& $payload['assigned_by'] === $assigner->id;
		}
	);

	Notification::assertCount(1);
});

test('task reassignment to self sends no notification', function () {
	Notification::fake();

	$assigner = User::factory()->create();
	$project = Project::factory()->create();

	$task = Task::factory()->create([
		'project_id' => $project->id,
		'assigned_by' => $assigner->id,
		'assigned_to' => $assigner->id,
	]);

	$listener = new SendTaskAssignedNotification();
	$listener->handle(new TaskReassigned($task, $assigner));

	Notification::assertNothingSent();
});

test('dispatching task reassigned twice remains idempotent for notifications', function () {
	Notification::fake();

	$assigner = User::factory()->create();
	$assignee = User::factory()->create();
	$project = Project::factory()->create();

	$task = Task::factory()->create([
		'project_id' => $project->id,
		'assigned_by' => $assigner->id,
		'assigned_to' => $assignee->id,
	]);

	$listener = new SendTaskAssignedNotification();
	$listener->handle(new TaskReassigned($task, $assigner));
	$listener->handle(new TaskReassigned($task, $assigner));

	expect(Notification::sent($assignee, TaskAssigned::class))->toHaveCount(1);
	Notification::assertCount(1);
});
