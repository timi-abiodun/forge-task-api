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

test('cross-org attachment uniformly fails across show/download/destroy (403)', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $orgA = Organisation::factory()->create();
    $orgB = Organisation::factory()->create();

    $userA = User::factory()->create();
    $orgA->users()->attach($userA, ['role' => MembershipRole::ADMIN]);

    $projectB = Project::factory()->create(['organisation_id' => $orgB->id]);
    $taskB = Task::factory()->create([
        'project_id' => $projectB->id,
        'assigned_by' => $userA->id,
        'assigned_to' => $userA->id,
    ]);

    Sanctum::actingAs($userA);

    $file = UploadedFile::fake()->create('cross-org.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', 'local');

    $attachmentB = $taskB->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => 'local',
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $userA->id,
    ]);

    // Attempt to access taskB's attachment under orgA context
    $show = $this->getJson(
        "/api/v1/organisations/{$orgA->id}/tasks/{$taskB->id}/attachments/{$attachmentB->id}"
    );
    
    $show->assertStatus(Response::HTTP_FORBIDDEN);

    $download = $this->get(
        "/api/v1/organisations/{$orgA->id}/tasks/{$taskB->id}/attachments/{$attachmentB->id}/download"
    );
    $download->assertStatus(Response::HTTP_FORBIDDEN);

    $destroy = $this->deleteJson(
        "/api/v1/organisations/{$orgA->id}/tasks/{$taskB->id}/attachments/{$attachmentB->id}"
    );
    $destroy->assertStatus(Response::HTTP_FORBIDDEN);



    // Ensure the attachment row still exists (and file still present)
    $this->assertDatabaseHas('attachments', ['id' => $attachmentB->id]);
    $this->assertTrue(Storage::disk('local')->exists($attachmentB->file_path));
});

test('task-mismatch within same org returns 404 uniformly across show/download/destroy', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();

    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);

    $taskA = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $user->id,
        'assigned_to' => $user->id,
    ]);

    $taskB = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $user->id,
        'assigned_to' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create('task-a.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', 'local');

    $attachmentA = $taskA->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => 'local',
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $user->id,
    ]);

    // Make URL use taskB but attachment belongs to taskA => abort_if triggers 404 before policy.
    $show = $this->getJson(
        "/api/v1/organisations/{$org->id}/tasks/{$taskB->id}/attachments/{$attachmentA->id}"
    );
    $show->assertStatus(Response::HTTP_NOT_FOUND);

    $download = $this->get(
        "/api/v1/organisations/{$org->id}/tasks/{$taskB->id}/attachments/{$attachmentA->id}/download"
    );
    $download->assertStatus(Response::HTTP_NOT_FOUND);

    $destroy = $this->deleteJson(
        "/api/v1/organisations/{$org->id}/tasks/{$taskB->id}/attachments/{$attachmentA->id}"
    );
    $destroy->assertStatus(Response::HTTP_NOT_FOUND);

    $this->assertDatabaseHas('attachments', ['id' => $attachmentA->id]);
    $this->assertTrue(Storage::disk('local')->exists($attachmentA->file_path));
});

