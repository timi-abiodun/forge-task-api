<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('a user can register successfully', function () {
    // Act: Provide ALL required data
    $response = $this->postJson('/api/v1/register', [
        'first_name' => 'John',
        'last_name'  => 'Snow',
        'email'      => 'john@email.com',
        'password'   => 'securePassword123',
        'password_confirmation' => 'securePassword123',
        'organisation_name' => 'Regal'
    ]);

    // Assert: Match the Controller/Resource structure
    $response->assertStatus(201)
             ->assertJsonStructure([
                'user' => ['id', 'first_name', 'last_name', 'email'],
                'organisation' => ['id', 'name'], // Match OrganisationResource
                'meta' => [
                    'access_token',
                    'token_type'
                ]
             ]);

    // 3. Assert: Database check
    $this->assertDatabaseHas('users', [
        'email' => 'john@email.com',
    ]);

    // 4. Assert: Database check for Organisation
    $this->assertDatabaseHas('organisations', [
        'name' => 'Regal',
    ]);

    // 5. Assert: Password Security
    $user = User::where('email', 'john@email.com')->first();
    expect(Hash::check('securePassword123', $user->password))->toBeTrue();
});

test('registration fails if email is already taken', function () {
    // Arrange: Create an existing user
    User::factory()->create(['email' => 'duplicate@oau.edu.ng']);

    // Act
    $response = $this->postJson('/api/v1/register', [
        'first_name' => 'first',
        'last_name' => 'last',
        'email' => 'duplicate@oau.edu.ng',
        'organisation_name' => 'Regal',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Assert
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('registration fails with an invalid email format', function () {
    $response = $this->postJson('/api/v1/register', [
        'first_name' => 'first',
        'last_name' => 'last',
        'email' => 'not-an-email-address',
        'organisation_name' => 'Regal',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('email must be at most 255 characters', function () {
    $response = $this->postJson('/api/v1/register', [
        'first_name' => 'first',
        'last_name' => 'last',
        'email' => str_repeat('a', 247) . '@test.com', // Total 256 chars       
        'organisation_name' => 'Regal', 
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('it fails if first_name or last_name are missing', function () {
    $this->postJson('/api/v1/register', [
        'first_name' => '',
        'last_name' => '',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['first_name', 'last_name']);
});

test('it generates a valid UUID for the user id', function () {
    $response = $this->postJson('/api/v1/register', [
        'first_name'        => 'Test',
        'last_name'         => 'User',
        'email'             => 'test@oau.edu.ng',
        'organisation_name' => 'Great Ife',
        'password'          => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Check status first to catch validation errors
    $response->assertStatus(201);

    $user = User::where('email', 'test@oau.edu.ng')->first();
    
    // 1. Assert the ID is not empty
    expect($user->id)->not->toBeEmpty();

    // 2. Assert it's a valid UUID format
    expect(Str::isUuid($user->id))->toBeTrue();
    
    // Assert it's a version 7 UUID (standard for Laravel's Str::uuid())
    // This is a "Senior" level assertion
    expect(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $user->id))
        ->toBe(1);
});