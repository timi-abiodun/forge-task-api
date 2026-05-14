<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;


Route::prefix('v1')->group(function () {
    
    // Auth: Restricted by 'auth' rate limiter (5 req/min)
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    });

    // Protected: Requires valid token
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/user', function (Request $request){
            return new UserResource($request->user());
        });
            
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');
    });
});