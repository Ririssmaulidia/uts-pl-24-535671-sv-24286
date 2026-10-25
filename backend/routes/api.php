<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Support\Facades\Route;

// Satu-satunya endpoint yang boleh diakses tanpa token.
Route::post('/login', [AuthController::class, 'login']);

// Semua endpoint di bawah ini wajib membawa token (Authorization: Bearer ...).
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('expenses', ExpenseController::class);
});
