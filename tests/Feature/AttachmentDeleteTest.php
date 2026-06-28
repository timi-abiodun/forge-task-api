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

test('uploader can delete their own attachment (204) and file is removed from disk', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();

    $uploader = User::factory()->create();
    $org->users()->attach($uploader, ['role' => MembershipRole::MEMBER]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $uploader->id,
        'assigned_to' => $uploader->id,
    ]);

    Sanctum::actingAs($uploader);

    $file = UploadedFile::fake()->create('spec.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', 'local');

    $attachment = $task->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => 'local',
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $uploader->id,
    ]);

    expect(Storage::disk('local')->exists($attachment->file_path))->toBeTrue();

    $response = $this->delete(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments/{$attachment->id}"
    );



    // route parameter binding expects UUIDs for BOTH {task} and {attachment}
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseCount('attachments', 0);

    // File removed from disk (not just DB row)
    Storage::assertMissing($attachment->file_path);
});

test('admin can delete any attachment in their org (204) and file is removed from disk', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();

    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $member = User::factory()->create();
    $org->users()->attach($member, ['role' => MembershipRole::MEMBER]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $member->id,
        'assigned_to' => $member->id,
    ]);

    Sanctum::actingAs($member);

    $file = UploadedFile::fake()->create('member.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', 'local');

    $attachment = $task->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => 'local',
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $member->id,
    ]);

    expect(Storage::disk('local')->exists($attachment->file_path))->toBeTrue();

    Sanctum::actingAs($admin);

    $response = $this->delete(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments/{$attachment->id}"
    );

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseCount('attachments', 0);
    Storage::assertMissing($attachment->file_path);
});

test('regular non-uploader, non-admin org member cannot delete (403) - precedence regression guard', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();

    $uploader = User::factory()->create();
    $org->users()->attach($uploader, ['role' => MembershipRole::MEMBER]);

    $otherMember = User::factory()->create();
    $org->users()->attach($otherMember, ['role' => MembershipRole::MEMBER]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $uploader->id,
        'assigned_to' => $uploader->id,
    ]);

    Sanctum::actingAs($uploader);

    $file = UploadedFile::fake()->create('uploader.pdf', 10, 'application/pdf');
    $storedPath = $file->store('attachments', 'local');

    $attachment = $task->attachments()->create([
        'file_name' => $file->getClientOriginalName(),
        'file_path' => $storedPath,
        'file_disk' => 'local',
        'mime_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
        'uploaded_by' => $uploader->id,
    ]);

    expect(Storage::disk('local')->exists($attachment->file_path))->toBeTrue();

    // Act as a different regular member
    Sanctum::actingAs($otherMember);

    $response = $this->delete(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments/{$attachment->id}"
    );

    $response->assertStatus(Response::HTTP_FORBIDDEN);

    // Ensure nothing got deleted
    $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    expect(Storage::disk('local')->exists($attachment->file_path))->toBeTrue();
});

test('deleting succeeds gracefully if file is already missing from disk (no 500) and attachment row is removed', function () {
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

    // Intentionally do NOT store this file on disk
    $missingPath = 'attachments/missing-on-disk.pdf';

    $attachment = $task->attachments()->create([
        'file_name' => 'missing-on-disk.pdf',
        'file_path' => $missingPath,
        'file_disk' => 'local',
        'mime_type' => 'application/pdf',
        'file_size' => 123,
        'uploaded_by' => $admin->id,
    ]);

    expect(Storage::disk('local')->exists($missingPath))->toBeFalse();

    $response = $this->delete(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments/{$attachment->id}"
    );

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseCount('attachments', 0);

    // Still missing on disk; but importantly, the operation should not error.
    Storage::assertMissing($missingPath);
});

