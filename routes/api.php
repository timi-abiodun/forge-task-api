<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;



Route::prefix('v1')->group(function () {
    
    // Auth: Restricted by 'auth' rate limiter (5 req/min)
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    });

    // Protected routes: Requires valid Sanctum token
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::get('/user', function (Request $request){
            return new UserResource($request->user());
        });

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        // Organisation-specific routes
        // This nesting ensures we know WHO the user is before checking their ORG
        Route::prefix('/organisations/{organisation}')
            ->middleware('org_context')
            ->group(function () {
                Route::get('/projects', [ProjectController::class, 'index']);
                Route::get('/tasks', [TaskController::class, 'index']);
        });   
            
        
    });
});