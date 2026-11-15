<?php

use App\Http\Controllers\PublicStatsController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:300,1')->get('/stats-publiques', PublicStatsController::class);

Route::get('/health', function () {
    return response()->json([
        'service' => 'gateway',
        'status' => 'ok',
        'message' => 'Gateway opérationnelle.',
    ]);
});

Route::get('/me', function () {
    return response()->json([
        'user' => null,
        'message' => 'Session non authentifiée.',
    ]);
});
