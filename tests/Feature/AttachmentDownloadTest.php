<?php

declare(strict_types=1);

use App\Enums\MembershipRole;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authorized admin can download attachment and filename is set', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $admin->id,
        'assigned_to' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $file = UploadedFile::fake()->create('spec.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', config('attachments.disk'));

    $attachment = $task->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => config('attachments.disk'),
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $admin->id,
    ]);

    $response = $this->get(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments/{$attachment->id}/download"
    );

    $response->assertStatus(Response::HTTP_OK);

    $contentDisposition = $response->headers->get('Content-Disposition');
    expect($contentDisposition)->toContain('attachment');
    expect($contentDisposition)->toContain('spec.pdf');
});

test('db row exists but file is missing on disk returns 404 (no 500)', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $admin->id,
        'assigned_to' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $attachment = $task->attachments()->create([
        'file_name' => 'missing.pdf',
        'file_path' => 'attachments/missing.pdf',
        'file_disk' => 'local',
        'mime_type' => 'application/pdf',
        'file_size' => 123,
        'uploaded_by' => $admin->id,
    ]);

    $response = $this->get(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments/{$attachment->id}/download"
    );

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('attachment download for a different task under same org returns 404', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);

    $taskA = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $admin->id,
        'assigned_to' => $admin->id,
    ]);

    $taskB = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $admin->id,
        'assigned_to' => $admin->id,
    ]);

    $file = UploadedFile::fake()->create('task-a.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', 'local');

    $attachment = $taskA->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => 'local',
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->get(
        "/api/v1/organisations/{$org->id}/tasks/{$taskB->id}/attachments/{$attachment->id}/download"
    );

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('attachment download for different organisation returns 403', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $orgA = Organisation::factory()->create();
    $orgB = Organisation::factory()->create();

    $admin = User::factory()->create();
    $orgA->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $projectA = Project::factory()->create(['organisation_id' => $orgA->id]);
    $taskA = Task::factory()->create([
        'project_id' => $projectA->id,
        'assigned_by' => $admin->id,
        'assigned_to' => $admin->id,
    ]);

    $projectB = Project::factory()->create(['organisation_id' => $orgB->id]);
    $taskB = Task::factory()->create([
        'project_id' => $projectB->id,
        'assigned_by' => $admin->id,
        'assigned_to' => $admin->id,
    ]);

    $file = UploadedFile::fake()->create('cross-org.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', 'local');

    $attachment = $taskA->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => 'local',
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->get(
        "/api/v1/organisations/{$orgB->id}/tasks/{$taskB->id}/attachments/{$attachment->id}/download"
    );

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

