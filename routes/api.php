<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationController;
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
                Route::post('/projects', [ProjectController::class, 'store']);
                Route::get('/projects/{project}', [ProjectController::class, 'show']);
                Route::put('/projects/{project}', [ProjectController::class, 'update']);
                Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
            
                Route::get('/tasks', [TaskController::class, 'index']);
                Route::post('/tasks', [TaskController::class, 'store']);
                Route::get('/tasks/{task}', [TaskController::class, 'show']);
                Route::put('/tasks/{task}', [TaskController::class, 'update']);
                Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

                Route::post('/invitations', [InvitationController::class, 'store']);
                Route::get('/invitations', [InvitationController::class, 'index']);
                Route::delete('/invitations/{invitation}', [InvitationController::class,'destroy']);
                Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend']);
        });   
        
    });

    // Public Routes for invitees
    Route::get('/invitations/{token}', [InvitationController::class, 'retrieve']);
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
    Route::post('/invitations/{token}/reject', [InvitationController::class, 'reject']);
});



// Route::prefix('v1')->group(function () {
    
//     // Auth: Restricted by 'auth' rate limiter (5 req/min)
//     Route::middleware('throttle:auth')->group(function () {
//         Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
//         Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
//     });

//     // Protected routes: Requires valid Sanctum token
//     Route::middleware('auth:sanctum')->group(function () {
        
//         Route::get('/user', function (Request $request) {
//             return new UserResource($request->user());
//         });

//         Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

//         // Organisation-specific nested resources
//         Route::middleware('org_context')->group(function () {
            
//             // 1. Automatically builds all 5 CRUD routes for projects:
//             // organisations/{organisation}/projects
//             // organisations/{organisation}/projects/{project}
//             // ->scoped() ensures a 404 is thrown if a project doesn't belong to the organisation
//             Route::apiResource('organisations.projects', ProjectController::class)->scoped([
//                 'organisation' => 'id',
//                 'project' => 'id',
//             ]);

//             // 2. Extra shallow/nested routes like tasks can be bound cleanly:
//             Route::get('organisations/{organisation}/tasks', [TaskController::class, 'index'])->name('organisations.tasks.index');
            
//         });   
//     });
// });