<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\OrganisationSwitchController;
use App\Http\Controllers\WebProjectController;
use App\Http\Controllers\WebOrganisationController;
use App\Http\Controllers\WebTaskController;
use App\Http\Controllers\WebAttachmentController;
use App\Http\Controllers\WebInvitationController;



// Route::get('/', function () {
//     return view('welcome');
// });

// Swagger UI (OpenAPI) at /api/docs
Route::get('/api/docs', function () {
    return response()->file(public_path('swagger-ui/index.html'));
});


Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);

    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});



Route::middleware('auth')->post('/organisations/switch', [OrganisationSwitchController::class, 'switch'])
    ->name('organisations.switch');

Route::middleware('auth')->group(function () {
    Route::get('/organisations/create', [WebOrganisationController::class, 'create'])->name('organisations.create');
    Route::post('/organisations', [WebOrganisationController::class, 'store'])->name('organisations.store');
});

Route::middleware(['auth', 'active_org'])->group(function () {

    Route::get('/projects', [WebProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [WebProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [WebProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [WebProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [WebProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [WebProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [WebProjectController::class, 'destroy'])->name('projects.destroy');
    
    Route::get('/projects/{project}/tasks/create', [WebTaskController::class, 'create'])->name('tasks.create');
    Route::post('/projects/{project}/tasks', [WebTaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [WebTaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{task}/edit', [WebTaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [WebTaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [WebTaskController::class, 'destroy'])->name('tasks.destroy');

    Route::post('/tasks/{task}/attachments', [WebAttachmentController::class, 'store'])->name('attachments.store');
    Route::get('/tasks/{task}/attachments/{attachment}/download', [WebAttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/tasks/{task}/attachments/{attachment}', [WebAttachmentController::class, 'destroy'])->name('attachments.destroy');
    

    // Admin-side 
    Route::get('/invitations', [WebInvitationController::class, 'index'])->name('invitations.index');
    Route::get('/invitations/create', [WebInvitationController::class, 'create'])->name('invitations.create');
    Route::post('/invitations', [WebInvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{invitation}', [WebInvitationController::class, 'destroy'])->name('invitations.destroy');
    Route::post('/invitations/{invitation}/resend', [WebInvitationController::class, 'resend'])->name('invitations.resend');

    


});
// Public - no auth needed
Route::get('/invite/{token}', [WebInvitationController::class, 'show'])->name('invitations.public.show');
Route::post('/invite/{token}/accept', [WebInvitationController::class, 'accept'])->name('web.invitations.accept');
Route::post('/invite/{token}/reject', [WebInvitationController::class, 'reject'])->name('web.invitations.reject');

// New-account password setup - public, token-gated by Laravel's password broker
Route::get('/password/set', [WebInvitationController::class, 'showSetPassword'])->name('password.set');
Route::post('/password/set', [WebInvitationController::class, 'submitSetPassword'])->name('password.set.submit');