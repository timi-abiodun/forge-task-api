<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Swagger UI (OpenAPI) at /api/docs
Route::get('/api/docs', function () {
    return response()->file(public_path('swagger-ui/index.html'));
});

