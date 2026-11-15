<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal.key')->group(function () {
    Route::get('/public/stats', [OrderController::class, 'publicStats']);

    Route::get('/health', fn () => response()->json([
        'service' => 'order-service',
        'status' => 'ok',
        'message' => 'Service commandes opérationnel.',
    ]));

    Route::get('/commandes', [OrderController::class, 'index']);
    Route::post('/commandes', [OrderController::class, 'store']);
    Route::get('/commandes/{commande}', [OrderController::class, 'show']);
    Route::patch('/commandes/{commande}/statut', [OrderController::class, 'updateStatus']);
    Route::patch('/commandes/{commande}/livreur', [OrderController::class, 'assignCourier']);
    Route::get('/livreur/commandes', [OrderController::class, 'courierMissions']);
    Route::patch('/livreur/commandes/{commande}/statut', [OrderController::class, 'completeMission']);
    Route::patch('/commandes/{commande}/annuler', [OrderController::class, 'cancel']);

    Route::get('/ordonnances', [OrderController::class, 'ordonnances']);
    Route::get('/ordonnances/{ordonnance}', [OrderController::class, 'ordonnanceShow']);
    Route::get('/ordonnances/{ordonnance}/fichier', [OrderController::class, 'showFile']);
    Route::patch('/ordonnances/{ordonnance}/valider', [OrderController::class, 'valider']);
    Route::patch('/ordonnances/{ordonnance}/refuser', [OrderController::class, 'refuser']);

    Route::post('/commandes/{commande}/paiement', [OrderController::class, 'paiement']);
    Route::get('/statistiques', [OrderController::class, 'stats']);
    Route::get('/notifications', [OrderController::class, 'notifications']);
    Route::patch('/notifications/lu', [OrderController::class, 'markNotificationsAsRead']);
});
