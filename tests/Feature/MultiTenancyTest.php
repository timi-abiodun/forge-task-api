<?php

use App\Models\Organisation;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;


test('a user can access their own orgs projects', function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => 'admin']);
    
    Project::factory()->create([
        'organisation_id' => $org->id,
        'name' => 'Internal Project'
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->getJson("/api/v1/organisations/{$org->id}/projects");

    // Assert
    $response->assertStatus(200)
             ->assertJsonFragment(['name' => 'Internal Project']);
});

test('a user cannot access another orgs projects', function () {
    // Arrange: Create two separate ecosystems
    $orgA = Organisation::factory()->create();
    $userA = User::factory()->create();
    $orgA->users()->attach($userA, ['role' => 'admin']);

    $orgB = Organisation::factory()->create();
    Project::factory()->create(['organisation_id' => $orgB->id, 'name' => 'Secret Org B Project']);

    // Act: User A tries to hit Org B's URL
    Sanctum::actingAs($userA);
    $response = $this->getJson("/api/v1/organisations/{$orgB->id}/projects");

    // Assert: Blocked by SetActiveOrganisation middleware
    $response->assertStatus(403);
});

test('the global scope prevents data leakage even if the middleware fails', function () {
    // Arrange
    $orgA = Organisation::factory()->create();
    $userA = User::factory()->create();
    $orgA->users()->attach($userA, ['role' => 'admin']);

    $orgB = Organisation::factory()->create();
    $projectB = Project::factory()->create(['organisation_id' => $orgB->id]);

    // Act: We simulate being "inside" Org A
    Sanctum::actingAs($userA);
    
    // We manually set the request attribute to Org A (simulating the middleware work)
    request()->attributes->set('organisation', $orgA);

    // Assert: Even a direct Eloquent query for "all" projects should NOT see Org B
    $projects = Project::all();
    
    // Use Laravel's collection 'contains' method
    expect($projects->contains('id', $projectB->id))->toBeFalse();
    expect($projects->count())->toBe(0);
});