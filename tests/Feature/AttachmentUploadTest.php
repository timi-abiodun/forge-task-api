<?php

declare(strict_types=1);

use App\Enums\MembershipRole;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Task;
use App\Models\Attachment;
use App\Models\User;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authorized admin can upload attachment (201) and file/disk metadata is persisted', function () {

    Storage::fake();

    // Ensure we can assert the disk used at upload time
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

    $response = $this->post(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments",
        ['attachment' => $file]
    );

    $taskFromDb = Task::findOrFail($task->id);
    expect($taskFromDb->id)->toBe($task->id);

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseCount('attachments', 1);

    $attachment = Attachment::findOrFail($response->json('id'));

    $this->assertSame($task->id, $attachment->task_id);
    $this->assertSame($admin->id, $attachment->uploaded_by);
    $this->assertSame(config('attachments.disk'), $attachment->file_disk);

    // File exists on the configured disk
    $this->assertTrue(Storage::disk($attachment->file_disk)->exists($attachment->file_path));
});

test('unauthorized org member cannot upload attachment (403)', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();

    $admin = User::factory()->create();
    $member = User::factory()->create();

    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);
    $org->users()->attach($member, ['role' => MembershipRole::MEMBER]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);

    // Task is NOT assigned to/from $member
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assigned_by' => $admin->id,
        'assigned_to' => $admin->id,
    ]);

    Sanctum::actingAs($member);

    $file = UploadedFile::fake()->create('spec.pdf', 5, 'application/pdf');

$response = $this->post(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments",
        ['attachment' => $file]
    );

    $response->assertStatus(Response::HTTP_FORBIDDEN);
    $this->assertDatabaseCount('attachments', 0);
});

test('user cannot upload attachment to a task in a different organisation (403)', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $orgA = Organisation::factory()->create();
    $orgB = Organisation::factory()->create();

    $userA = User::factory()->create();
    $orgA->users()->attach($userA, ['role' => MembershipRole::ADMIN]);

    $projectB = Project::factory()->create(['organisation_id' => $orgB->id]);
    $taskB = Task::factory()->create(['project_id' => $projectB->id]);

    Sanctum::actingAs($userA);

    $file = UploadedFile::fake()->create('spec.pdf', 5, 'application/pdf');

    $response = $this->postJson(
        "/api/v1/organisations/{$orgA->id}/tasks/{$taskB->id}/attachments",
        ['attachment' => $file]
    );

    $response->assertStatus(Response::HTTP_FORBIDDEN);
    $this->assertDatabaseCount('attachments', 0);
});

test('attachment id valid but belongs to a different task than URL returns 404', function () {
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

    // Attachment belongs to taskA, but request will be made under taskB.
    // Note: route model binding expects `{task}` and `{attachment}` to be UUIDs of those models.
    $attachment = $taskA->attachments()->create([

        'file_name' => 'real.pdf',
        'file_path' => 'attachments/real.pdf',
        'file_disk' => 'local',
        'mime_type' => 'application/pdf',
        'file_size' => 123,
        'uploaded_by' => $admin->id,
        'task_id' => $taskA->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson(
        "/api/v1/organisations/{$org->id}/tasks/{$taskB->id}/attachments/{$attachment->id}"
    );


    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('missing file upload returns 422 and does not create attachment row', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    Sanctum::actingAs($admin);

    $response = $this->postJson(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments",
        []
    );

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $this->assertDatabaseCount('attachments', 0);
});


test('file exceeding size limit returns 422', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    Sanctum::actingAs($admin);

    // max:10240 where Laravel interprets `max` for `file` as KB.
    // Use 11MB ~ 11264 KB to exceed.
    $file = UploadedFile::fake()->create('too-large.pdf', 11 * 1024, 'application/pdf');

    $response = $this->postJson(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments",
        ['attachment' => $file]
    );

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $this->assertDatabaseCount('attachments', 0);
});

test('disallowed mime type returns 422', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    Sanctum::actingAs($admin);

    // Create a .exe with an exe mimetype (not allowed by request rules)
    $file = UploadedFile::fake()->create('malware.exe', 1, 'application/x-msdownload');

    $response = $this->postJson(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments",
        ['attachment' => $file]
    );

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $this->assertDatabaseCount('attachments', 0);
});

test('uploaded_by cannot be spoofed from request body (uploaded_by = authenticated user)', function () {
    Storage::fake();
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();

    $realUser = User::factory()->create();
    $org->users()->attach($realUser, ['role' => MembershipRole::ADMIN]);

    $spoofedUser = User::factory()->create();

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    Sanctum::actingAs($realUser);

    $file = UploadedFile::fake()->create('spec.pdf', 5, 'application/pdf');

    $response = $this->postJson(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments",
        [
            'attachment' => $file,
            // Should be ignored because controller sets uploaded_by from auth()->id()
            'uploaded_by' => $spoofedUser->id,
        ]
    );

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('attachments', [
        'task_id' => $task->id,
        'uploaded_by' => $realUser->id,
    ]);
});

test('task attachments index returns only attachments for the specified task (and not other tasks in same organisation)', function () {
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    $projectA = Project::factory()->create(['organisation_id' => $org->id]);
    $projectB = Project::factory()->create(['organisation_id' => $org->id]);

    $taskA = Task::factory()->create(['project_id' => $projectA->id]);
    $taskB = Task::factory()->create(['project_id' => $projectB->id]);

    $taskAAttachments = collect(range(1, 3))->map(function (int $i) use ($taskA, $user) {
        return $taskA->attachments()->create([
            'file_name' => "task-a-{$i}.pdf",
            'file_path' => "attachments/task-a-{$i}.pdf",
            'file_disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => 123,
            'uploaded_by' => $user->id,
            'task_id' => $taskA->id,
            'created_at' => now()->subMinutes(10 - $i),
            'updated_at' => now()->subMinutes(10 - $i),
        ]);
    });

    $taskBAttachments = collect(range(1, 2))->map(function (int $i) use ($taskB, $user) {
        return $taskB->attachments()->create([
            'file_name' => "task-b-{$i}.pdf",
            'file_path' => "attachments/task-b-{$i}.pdf",
            'file_disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => 456,
            'uploaded_by' => $user->id,
            'task_id' => $taskB->id,
            'created_at' => now()->subMinutes(10 - $i),
            'updated_at' => now()->subMinutes(10 - $i),
        ]);
    });

    Sanctum::actingAs($user);

    $response = $this->getJson(
        "/api/v1/organisations/{$org->id}/tasks/{$taskA->id}/attachments"
    );

    $response->assertOk();

    $returnedIds = collect($response->json('data.*.id'));

    expect($returnedIds)->toHaveCount(3);
    expect($returnedIds)->toContain($taskAAttachments->get(0)->id);
    expect($returnedIds)->toContain($taskAAttachments->get(1)->id);
    expect($returnedIds)->toContain($taskAAttachments->get(2)->id);

    // Ensure no attachments from other tasks are returned
    expect($returnedIds)->not->toContain($taskBAttachments->get(0)->id);
    expect($returnedIds)->not->toContain($taskBAttachments->get(1)->id);
});

test('unauthenticated org non-member cannot list task attachments (403)', function () {
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $member = User::factory()->create();
    $org->users()->attach($member, ['role' => MembershipRole::ADMIN]);

    $nonMember = User::factory()->create();

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $task->attachments()->create([
        'file_name' => 'spec.pdf',
        'file_path' => 'attachments/spec.pdf',
        'file_disk' => 'local',
        'mime_type' => 'application/pdf',
        'file_size' => 123,
        'uploaded_by' => $member->id,
        'task_id' => $task->id,
    ]);

    Sanctum::actingAs($nonMember);

    $response = $this->getJson(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments"
    );

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('task attachments index pagination works (page 2 returns records after first 15)', function () {
    config(['attachments.disk' => 'local']);

    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    $project = Project::factory()->create(['organisation_id' => $org->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    // Create 16 attachments so we must paginate to page 2
    $attachments = collect(range(1, 16))->map(function (int $i) use ($task, $user) {
        return $task->attachments()->create([
            'file_name' => "spec-{$i}.pdf",
            'file_path' => "attachments/spec-{$i}.pdf",
            'file_disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => 1000 + $i,
            'uploaded_by' => $user->id,
            'task_id' => $task->id,
            // Make pagination deterministic by created_at order
            'created_at' => now()->addMinutes($i),
            'updated_at' => now()->addMinutes($i),
        ]);
    });

    Sanctum::actingAs($user);

    // Laravel will paginate with per-page=15 (controller)
    $page1 = $this->getJson(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments?page=1"
    );
    $page2 = $this->getJson(
        "/api/v1/organisations/{$org->id}/tasks/{$task->id}/attachments?page=2"
    );



    $page1->assertOk();
    $page2->assertOk();

    $page1Ids = collect($page1->json('data.*.id'));
    $page2Ids = collect($page2->json('data.*.id'));

    expect($page1Ids)->toHaveCount(15);
    expect($page2Ids)->toHaveCount(1);

    // Page 2 should contain the attachment created last
    $expectedLastId = $attachments->last()->id;
    expect($page2Ids)->toContain($expectedLastId);
    expect($page1Ids)->not->toContain($expectedLastId);
});


