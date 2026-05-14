<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated user can logout and revoke their token', function () {
    // 1. Arrange: Create a user and a token
    $user = User::factory()->create();
    
    // We act as the user using Sanctum's helper to simulate a valid token
    Sanctum::actingAs($user);

    // 2. Act: Hit the logout endpoint
    $response = $this->postJson('/api/v1/logout');

    // 3. Assert: Verify the response and the database state
    $response->assertStatus(200)
             ->assertJson(['message' => 'Logged out successfully']);

    // Check that the user has no tokens left in the database
    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated user cannot access logout endpoint', function () {
    // Act: Try to logout without being logged in
    $response = $this->postJson('/api/v1/logout');

    // Assert: Should be blocked by 'auth:sanctum' middleware
    $response->assertStatus(401);
});