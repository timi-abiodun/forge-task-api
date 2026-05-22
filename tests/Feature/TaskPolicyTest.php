<?php

use App\Enums\MembershipRole;
use App\Models\Organisation;
use App\Models\task;
use App\Models\User;
use App\Models\Project;
use App\Enums\TaskStatus;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;


test("member can view list of tasks", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::MEMBER]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->getJson("/api/v1/organisations/{$org->id}/tasks");

    // Assert
    $response->assertStatus(Response::HTTP_OK);
});

test("member can view task", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);
    
    // Act
    Sanctum::actingAs($user);
    $response = $this->getJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}");

    // Assert
    $response->assertStatus(Response::HTTP_OK);
});

test("member cannot create a task", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/tasks");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test("member cannot update a task", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->putJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test("member cannot delete a task", function () {
     // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test("admin can create a task", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $assignedBy = User::factory()->create();
    $assignedTo = User::factory()->create();
    $org->users()->attach($assignedBy, ["role"=> MembershipRole::ADMIN]);
    $org->users()->attach($assignedTo, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);

    $payload = [
        'project_id'   => $project->id,
        'name'         => 'New Task',
        'status'       => TaskStatus::TODO->value,
        'assigned_by'  => $assignedBy->id,
        'assigned_to'  => $assignedTo->id,
    ];

    // Act
    Sanctum::actingAs($assignedBy);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/tasks", $payload);

    // Assert
    $response->assertStatus(Response::HTTP_CREATED);
});

test("admin can delete a task", function () {
     // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::ADMIN]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}");

    // Assert
    $response->assertStatus(Response::HTTP_NO_CONTENT);
});

test("assignee can update task status successfully", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $assignedBy = User::factory()->create();
    $assignedTo = User::factory()->create();
    
    $org->users()->attach($assignedBy, ["role" => MembershipRole::ADMIN]);
    $org->users()->attach($assignedTo, ["role" => MembershipRole::MEMBER]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assignedBy->id,
        'assigned_to' => $assignedTo->id,
        'status' => TaskStatus::TODO,
    ]);

    // Act
    Sanctum::actingAs($assignedTo);
    
    // We pass ONLY the status change, which is allowed
    $response = $this->putJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}", [
        'status' => TaskStatus::COMPLETED->value, 
    ]);

    // Assert
    $response->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => TaskStatus::COMPLETED,
    ]);
});

test("assignee is forbidden from updating restricted task fields", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $assignedBy = User::factory()->create();
    $assignedTo = User::factory()->create();
    $org->users()->attach($assignedBy, ["role"=> MembershipRole::ADMIN]);
    $org->users()->attach($assignedTo, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assignedBy->id,
        'assigned_to' => $assignedTo->id,
        'name' => 'Original Task Name',
    ]);

    // Act
    Sanctum::actingAs($assignedTo);

    // We attempt to change a prohibited field ('name')
    $response = $this->putJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}", [
        'name' => 'Malicious Changed Name',
        'status' => TaskStatus::COMPLETED->value,
    ]);

    // Assert
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['name']);

    // Ensure database remained unchanged
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'name' => 'Original Task Name',
    ]);
});

test("assignee cannot delete task", function () {
     // Arrange
    $org = Organisation::factory()->create();
    $assignedBy = User::factory()->create();
    $assignedTo = User::factory()->create();
    $org->users()->attach($assignedBy, ["role"=> MembershipRole::ADMIN]);
    $org->users()->attach($assignedTo, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assignedBy->id,
        'assigned_to' => $assignedTo->id,
        'name' => 'Original Task Name',
    ]);

    // Act
    Sanctum::actingAs($assignedTo);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);

    // Ensure database remained unchanged
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'name' => 'Original Task Name',
    ]);
});

test("assigner can delete task", function () {
     // Arrange
    $org = Organisation::factory()->create();
    $assignedBy = User::factory()->create();
    $assignedTo = User::factory()->create();
    $org->users()->attach($assignedBy, ["role"=> MembershipRole::ADMIN]);
    $org->users()->attach($assignedTo, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assignedBy->id,
        'assigned_to' => $assignedTo->id,
    ]);

    // Act
    Sanctum::actingAs($assignedBy);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}");

    // Assert
    $response->assertStatus(Response::HTTP_NO_CONTENT);
});

test("assigner can update restricted task fields", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $assignedBy = User::factory()->create();
    $assignedTo = User::factory()->create();
    $org->users()->attach($assignedBy, ["role"=> MembershipRole::ADMIN]);
    $org->users()->attach($assignedTo, ["role"=> MembershipRole::MEMBER]);

    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $assignedBy->id,
        'assigned_to' => $assignedTo->id,
        'name' => 'Original Task Name',
    ]);

    // Act
    Sanctum::actingAs($assignedBy);

    // We attempt to change a prohibited field ('name')
    $response = $this->putJson("/api/v1/organisations/{$org->id}/tasks/{$task->id}", [
        'name' => 'Changed Name',
        'status' => TaskStatus::COMPLETED->value,
    ]);

    // Assert
    $response->assertStatus(Response::HTTP_OK);

    // Ensure database remained unchanged
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'name' => 'Changed Name',
    ]);
});
