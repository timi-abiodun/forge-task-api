<?php

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use App\Models\Invitation;
use App\Models\Organisation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;


test("admin can send an invitation", function () {
    Mail::fake();
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    // Define the request payload
    $payload = [
        'email' => 'newinvite@email.com',
        'role' => MembershipRole::MEMBER,
    ];

    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/invitations", $payload);

    // Assert
    $response->assertStatus(Response::HTTP_CREATED);
});

test("member cannot send an invitation", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::MEMBER]);

    // Define the request payload
    $payload = [
        'email' => 'newinvite@email.com',
        'role' => MembershipRole::MEMBER,
    ];

    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/invitations", $payload);

    // Assert
    $response->assertStatus(Response::HTTP_FORBIDDEN);

    $this->assertDatabaseMissing('invitations', [
        'email' => 'newinvite@email.com',
        'role' => MembershipRole::MEMBER,
    ]);
});


test("cannot invite someone with an active pending invitation", function () {
   // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    $invitee = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'organisation_id' => $org->id,
        'invited_by' => $user->id,
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
        'status' => InvitationStatus::PENDING,
    ]);

    // Define the request payload
    $payload = [
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
    ];

    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/invitations", $payload);

    // Assert
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});


test("cannot invite an existing member", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    $invitee = User::factory()->create();
    $org->users()->attach($invitee, ['role' => MembershipRole::MEMBER]);

    // Define the request payload
    $payload = [
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
    ];

    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/invitations", $payload);

    // Assert
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

test("can retrieve invitation details by token", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]); // This is working for member role fr some reason 

    $invitee = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'organisation_id' => $org->id,
        'invited_by' => $user->id,
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
        'status' => InvitationStatus::PENDING,
    ]);


    // Act
    Sanctum::actingAs($invitee);
    $response = $this->getJson("/api/v1/invitations/{$invitation->token}");

    // Assert
    $response->assertStatus(Response::HTTP_OK);
});

test("can accept a valid invitation", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]); 

    $invitee = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'organisation_id' => $org->id,
        'invited_by' => $user->id,
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
        'status' => InvitationStatus::PENDING,
    ]);


    // Act
    $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

    // Assert
    $response->assertStatus(Response::HTTP_OK);

    // Verify the record exists in the memberships table
    $this->assertDatabaseHas('organisation_memberships', [
        'user_id' => $invitee->id,
        'organisation_id' => $org->id,
        'role' => MembershipRole::MEMBER,
    ]);

    $this->assertDatabaseHas('invitations', [
        'id' => $invitation->id,
        'status' => InvitationStatus::ACCEPTED, 
    ]);
});

test("cannot accept an expired invitation", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]); 

    $invitee = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'organisation_id' => $org->id,
        'invited_by' => $user->id,
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
        'status' => InvitationStatus::EXPIRED,
    ]);


    // Act
    $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

    // Assert
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    // Verify the record exists in the memberships table
    $this->assertDatabaseMissing('organisation_memberships', [
        'user_id' => $invitee->id,
        'organisation_id' => $org->id,
        'role' => MembershipRole::MEMBER,
    ]);
});

test("cannot accept an already accepted invitation", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]); 

    $invitee = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'organisation_id' => $org->id,
        'invited_by' => $user->id,
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
        'status' => InvitationStatus::ACCEPTED,
    ]);


    // Act
    $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

    // Assert
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    // Verify the record exists in the memberships table
    $this->assertDatabaseMissing('organisation_memberships', [
        'user_id' => $invitee->id,
        'organisation_id' => $org->id,
        'role' => MembershipRole::MEMBER,
    ]);

    $this->assertDatabaseHas('invitations', [
        'id' => $invitation->id,
        'status' => InvitationStatus::ACCEPTED, 
    ]);
});

test("admin can revoke a pending invitation", function () {
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    $invitee = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'organisation_id' => $org->id,
        'invited_by' => $user->id,
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
        'status' => InvitationStatus::PENDING,
    ]);

    // Act
    Sanctum::actingAs($user);
    $response = $this->deleteJson("/api/v1/organisations/{$org->id}/invitations/$invitation->id");

    // Assert
    $response->assertStatus(Response::HTTP_OK);

    $this->assertDatabaseHas('invitations', [
        'id' => $invitation->id,
        'status' => InvitationStatus::REVOKED, 
    ]);
});


test("admin can resend a pending invitation", function () {
    Mail::fake();
    // Arrange
    $org = Organisation::factory()->create();
    $user = User::factory()->create();
    $org->users()->attach($user, ['role' => MembershipRole::ADMIN]);

    $invitee = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'organisation_id' => $org->id,
        'invited_by' => $user->id,
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
        'status' => InvitationStatus::PENDING,
    ]);

    // Define the request payload
    $payload = [
        'email' => $invitee->email,
        'role' => MembershipRole::MEMBER,
    ];


    // Act
    Sanctum::actingAs($user);
    $response = $this->postJson("/api/v1/organisations/{$org->id}/invitations/$invitation->id/resend", $payload);

    // Assert
    $response->assertStatus(Response::HTTP_OK);
});

