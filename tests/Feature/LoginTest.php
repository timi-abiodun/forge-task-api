<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

beforeEach(function () {
    RateLimiter::for('auth', function () {
        return Limit::none(); // Removes the limit for the test environment
    });
});

test('user can login with correct credentials', function () {
    // 1. Arrange: Create a user via Factory
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    // 2. Act: Login
    $response = $this->postJson('/api/v1/login', [
        'email'    => $user->email,
        'password' => 'password123',
    ]);

    // 3. Assert: Login success
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'user',
                 'meta' => ['access_token', 'token_type']
             ]);
    
    $token = $response->json('meta.access_token');

    // 4. Act & Assert: Verify the token actually works
    $this->withToken($token) // Clean helper for 'Bearer ' . $token
         ->getJson('/api/v1/user')
         ->assertStatus(200)
         ->assertJsonPath('data.email', $user->email); // Match your UserResource structure
});

test('user cannot login with incorrect password', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(422);
    $this->assertGuest();
});

test('login fails if email does not exist', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'ghost@oau.edu.ng', // Valid format, but doesn't exist
        'password' => 'any_password',
    ]);

    $response->assertStatus(422)
             ->assertJson([
                 'message' => 'The provided credentials are incorrect.'
             ]);
});

test('login requires both email and password', function () {
    $response = $this->postJson('/api/v1/login', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email', 'password']);
});