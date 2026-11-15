<?php

use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal.key')->group(function () {
    Route::get('/public/stats', [CatalogController::class, 'publicStats']);

    Route::get('/health', fn () => response()->json([
        'service' => 'catalog-service',
        'status' => 'ok',
        'message' => 'Service catalogue opérationnel.',
    ]));

    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::post('/categories', [CatalogController::class, 'storeCategory']);
    Route::put('/categories/{category}', [CatalogController::class, 'updateCategory']);
    Route::delete('/categories/{category}', [CatalogController::class, 'deleteCategory']);

    Route::get('/medicaments', [CatalogController::class, 'index']);
    Route::get('/medicaments/{medicament}', [CatalogController::class, 'show']);
    Route::post('/medicaments', [CatalogController::class, 'store']);
    Route::put('/medicaments/{medicament}', [CatalogController::class, 'update']);
    Route::delete('/medicaments/{medicament}', [CatalogController::class, 'destroy']);
    Route::post('/medicaments/verifier', [CatalogController::class, 'verifier']);
    Route::post('/medicaments/{medicament}/stock/diminuer', [CatalogController::class, 'diminuerStock']);
    Route::post('/medicaments/{medicament}/stock/reapprovisionner', [CatalogController::class, 'reapprovisionnerStock']);
    Route::get('/alertes', [CatalogController::class, 'alertes']);
});
