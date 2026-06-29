<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\OrganisationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AttachmentController;



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

        Route::get('/organisations', [OrganisationController::class, 'index']);
        Route::post('/organisations', [OrganisationController::class, 'store']);
        Route::get('/organisations/{organisation}', [OrganisationController::class, 'show']);
        Route::put('/organisations/{organisation}', [OrganisationController::class, 'update']);
        Route::delete('/organisations/{organisation}', [OrganisationController::class, 'destroy']);

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

                Route::prefix('/tasks/{task}')
                    ->group(function()
                    {
                        Route::get('/attachments', [AttachmentController::class, 'index']);
                        Route::post('/attachments', [AttachmentController::class, 'store']);
                        Route::get('/attachments/{attachment}', [AttachmentController::class, 'show']);
                        Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download']);
                        Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
                    });
                
                Route::post('/invitations', [InvitationController::class, 'store']);
                Route::get('/invitations', [InvitationController::class, 'index']);
                Route::delete('/invitations/{invitation}', [InvitationController::class,'destroy']);
                Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend']);        
        });   
        
    });

    // Public Routes for invitees
    Route::get('/invitations/{token}', [InvitationController::class, 'retrieve'])
        ->name('invitations.retrieve');
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->name('invitations.accept');
    Route::post('/invitations/{token}/reject', [InvitationController::class, 'reject'])
        ->name('invitations.reject');
});