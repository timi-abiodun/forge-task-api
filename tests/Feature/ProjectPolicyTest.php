<?php

use App\Enums\MembershipRole;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;


test("member can view list of projects", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::MEMBER]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->getJson("/api/v1/organisations/{$org->id}/projects");

    // Assert
    $response->assertStatus(Response::HTTP_OK);
});

test("member can view project", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    // Ensure the project actually belongs to this organization
    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->getJson("/api/v1/organisations/{$org->id}/projects/{$project->id}");

    // Assert
    $response->assertStatus(Response::HTTP_OK);
});

test("member cannot create a project", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/projects");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test("member cannot update a project", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    // Ensure the project actually belongs to this organization
    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->putJson("/api/v1/organisations/{$org->id}/projects/{$project->id}");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test("member cannot delete a project", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::MEMBER]);

    // Ensure the project actually belongs to this organization
    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}/projects/{$project->id}");

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test("admin can create a project", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::ADMIN]);

    // Define the request payload
    $payload = [
        'name' => 'New Forge API Project',
    ];

    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/projects/", $payload);

    // Assert
    $response->assertStatus(Response::HTTP_CREATED);
});

test("admin can delete a project", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ["role"=> MembershipRole::ADMIN]);

    // Ensure the project actually belongs to this organization
    $project = Project::factory()->create([
        'organisation_id' => $org->id,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}/projects/{$project->id}");

    // Assert
    $response->assertStatus(Response::HTTP_NO_CONTENT);
});