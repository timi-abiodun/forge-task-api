<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class LoginService
{
    /**
     * Handle user authentication and token generation.
    */

    public function login(array $data): array
    {
        // // Search by the correct identifier (email)
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        // Create and return token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token'=> $token
        ];
    }
}