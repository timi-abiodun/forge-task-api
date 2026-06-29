<?php

use App\Enums\MembershipRole;
use App\Models\Organisation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

test('owner updates own org', function () {
    // Arrange
    $org = Organisation::factory()->create();
    $owner = User::factory()->create();
    $org->users()->attach($owner, ['role' => MembershipRole::OWNER]);

    // Act
    Sanctum::actingAs($owner);
    $response = $this->putJson("/api/v1/organisations/{$org->id}", [
        'name' => 'Updated Org Name',
    ]);

    // Assert
    $response->assertStatus(Response::HTTP_OK);
    $this->assertSame('Updated Org Name', $org->refresh()->name);
});

test('admin updates own org', function () {
    // Arrange
    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    // Act
    Sanctum::actingAs($admin);
    $response = $this->putJson("/api/v1/organisations/{$org->id}", [
        'name' => 'Updated Org Name',
    ]);

    // Assert
    $response->assertStatus(Response::HTTP_OK);
    $this->assertSame('Updated Org Name', $org->refresh()->name);
});

test('member-but-not-admin cannot update org (403)', function () {
    // Arrange
    $org = Organisation::factory()->create();
    $member = User::factory()->create();
    $org->users()->attach($member, ['role' => MembershipRole::MEMBER]);

    // Act
    Sanctum::actingAs($member);
    $response = $this->putJson("/api/v1/organisations/{$org->id}", [
        'name' => 'Updated Org Name',
    ]);

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('admin cannot delete org (owner only) (403)', function () {
    // Arrange
    $org = Organisation::factory()->create();
    $admin = User::factory()->create();
    $org->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    // Act
    Sanctum::actingAs($admin);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('user with zero membership cannot update or delete org (403)', function () {
    // Arrange
    $org = Organisation::factory()->create();
    $outsider = User::factory()->create();

    // Act + Assert update
    Sanctum::actingAs($outsider);
    $updateResponse = $this->putJson("/api/v1/organisations/{$org->id}", [
        'name' => 'Updated Org Name',
    ]);
    $updateResponse->assertStatus(Response::HTTP_FORBIDDEN);

    // Act + Assert delete
    $deleteResponse = $this->deleteJson("/api/v1/organisations/{$org->id}");
    $deleteResponse->assertStatus(Response::HTTP_FORBIDDEN);
});

test('admin in one org cannot update a different org (403)', function () {
    $orgA = Organisation::factory()->create();
    $orgB = Organisation::factory()->create();
    $admin = User::factory()->create();

    // Admin in Org A, no membership at all in Org B
    $orgA->users()->attach($admin, ['role' => MembershipRole::ADMIN]);

    Sanctum::actingAs($admin);
    $response = $this->putJson("/api/v1/organisations/{$orgB->id}", [
        'name' => 'Hostile Rename',
    ]);

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('owner in one org cannot delete a different org (403)', function () {
    $orgA = Organisation::factory()->create();
    $orgB = Organisation::factory()->create();
    $admin = User::factory()->create();

    // Admin in Org A, no membership at all in Org B
    $orgA->users()->attach($admin, ['role' => MembershipRole::OWNER]);

    Sanctum::actingAs($admin);
    $response = $this->deleteJson("/api/v1/organisations/{$orgB->id}");

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});


test('member can view their own org (200)', function () {
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::MEMBER]);

    Sanctum::actingAs($user);
    $response = $this->getJson("/api/v1/organisations/{$org->id}");

    $response->assertStatus(Response::HTTP_OK);
});

test('outsider cannot view an org they are not a member of (403)', function () {
    $org = Organisation::factory()->create();
    $outsider = User::factory()->create();

    Sanctum::actingAs($outsider);
    $response = $this->getJson("/api/v1/organisations/{$org->id}");

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('member can view any organisations when they belong to at least one (200)', function () {
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::MEMBER]);

    Sanctum::actingAs($user);
    $response = $this->getJson('/api/v1/organisations');

    $response->assertStatus(Response::HTTP_OK);
});

test('outsider cannot view any organisations (403)', function () {
    $outsider = User::factory()->create();

    Sanctum::actingAs($outsider);
    $response = $this->getJson('/api/v1/organisations');

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('user only sees organisations they belong to, not all organisations', function () {
    $myOrg = Organisation::factory()->create();
    $otherOrg = Organisation::factory()->create();
    $user = User::factory()->create();
    $myOrg->users()->attach($user, ['role' => MembershipRole::MEMBER]);

    Sanctum::actingAs($user);
    $response = $this->getJson('/api/v1/organisations');

    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonFragment(['id' => $myOrg->id]);
    $response->assertJsonMissing(['id' => $otherOrg->id]);
});
