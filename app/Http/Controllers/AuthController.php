<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\OrganisationResource;
use Illuminate\Http\Request;
use App\Services\RegisterService;
use App\Services\LoginService;
use App\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        protected RegisterService $registerService,
        protected LoginService $loginService

    ){}

    public function register(RegisterRequest $request)
    {
        // Fetch validated input
        $data = $request->validated();

        // Destructure the array returned by the service
        [
            'user'         => $user, 
            'organisation' => $organisation,
            'token'        => $token
        ] = $this->registerService->register($data);

        return response()->json([
            'user' => new UserResource($user),
            'organisation' => new OrganisationResource($organisation),
            'meta' => [
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ],
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request){
        // Fetch Validated Input
        $data = $request->validated();
        
        $result = $this->loginService->login($data);        

        return response()->json([
            'user' => new UserResource($result['user']),
            'meta' => [
                'access_token' => $result['token'],
                'token_type'   => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request){
            // Delete the token that was used to authenticate the current request
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully'
            ], Response::HTTP_OK);
    }
}
