<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Organisation;
use App\Models\User;
use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organisation_id' => Organisation::factory(),
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => MembershipRole::MEMBER->value,
            'token' => Str::random(40),
            'status' => InvitationStatus::PENDING->value,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the invitation has been accepted.
     */
    public function accepted(User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::ACCEPTED->value,
            'accepted_by' => $user ?? User::factory(),
            'accepted_at' => now(),
        ]);
    }
}