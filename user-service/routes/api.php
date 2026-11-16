<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/password/email', [UserController::class, 'sendPasswordResetLink'])->middleware('throttle:5,1');
Route::post('/password/reset', [UserController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::patch('/me', [UserController::class, 'updateProfile']);
    Route::patch('/me/password', [UserController::class, 'updatePassword']);
});

Route::middleware(['auth:sanctum', 'internal.key'])->group(function () {
    Route::get('/health', fn () => response()->json([
        'service' => 'user-service',
        'status' => 'ok',
        'message' => 'Service utilisateurs opérationnel.',
    ]));

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'storeStaff']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::post('/users/{user}/invitation', [UserController::class, 'resendInvitation']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

Route::middleware('internal.key')->get('/public/stats', [UserController::class, 'publicStats']);
