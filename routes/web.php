<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;

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
