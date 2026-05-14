<?php

namespace App\Services;

use App\Models\User;
use App\Models\Organisation;
use App\Enums\MembershipRole;
use Illuminate\Support\Facades\DB;


class RegisterService
{
    /**
     * Handle the registration of a new user and their primary organisation.
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Create the User
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'password'   => $data['password'],
            ]);

            // Create the Organisation
            $organisation = Organisation::create([
                'name' => $data['organisation_name'],
            ]);

            // Create the Membership (linking User to Org)
            // Note: invited_by is null because they are the creator
            $membership = $organisation->memberships()->create([
                'user_id'    => $user->id,
                'role'       => MembershipRole::OWNER,
                'invited_by' => null, 
                'invited_at' => now(),
            ]);

            // Create and return token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Return everything as an array
            return [
                'user'         => $user,
                'organisation' => $organisation,
                'membership'   => $membership,
                'token'        => $token,
            ];
        });
    }
}