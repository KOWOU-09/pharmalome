<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueAdminController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\PharmacienController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GatewayController::class, 'home'])->name('home');
Route::view('/a-propos', 'about')->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/catalogue', [GatewayController::class, 'catalog'])->name('catalogue');
Route::get('/medicaments/{id}', [GatewayController::class, 'showProduct'])->name('medicament.show');
Route::get('/panier', [GatewayController::class, 'cart'])->name('panier');
Route::get('/checkout', [GatewayController::class, 'checkout'])->middleware('gateway.auth')->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware(['gateway.auth', 'throttle:10,1'])->name('checkout.store');
Route::get('/commandes', [GatewayController::class, 'orders'])->middleware('gateway.auth')->name('commandes');
Route::get('/compte', [GatewayController::class, 'account'])->middleware('gateway.auth')->name('compte');
Route::patch('/compte', [AuthController::class, 'updateProfile'])->middleware('gateway.auth')->name('compte.update');
Route::patch('/compte/mot-de-passe', [AuthController::class, 'updatePassword'])->middleware('gateway.auth')->name('compte.password.update');
Route::get('/connexion', [GatewayController::class, 'login'])->name('login');
Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
Route::get('/inscription', [GatewayController::class, 'register'])->name('register');
Route::post('/inscription', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
Route::get('/mot-de-passe/oublie', [GatewayController::class, 'forgotPassword'])->name('password.request');
Route::post('/mot-de-passe/email', [AuthController::class, 'sendPasswordResetLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/mot-de-passe/reinitialiser', [GatewayController::class, 'resetPassword'])->name('password.reset');
Route::post('/mot-de-passe/reinitialiser', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('gateway.auth')->name('logout');
Route::middleware(['gateway.auth', 'gateway.role:pharmacien,admin'])->group(function () {
    Route::get('/pharmacien', [PharmacienController::class, 'dashboard'])->name('pharmacien.dashboard');
    Route::get('/pharmacien/ordonnances', [PharmacienController::class, 'ordonnances'])->name('pharmacien.ordonnances');
    Route::patch('/pharmacien/ordonnances/{id}/valider', [PharmacienController::class, 'validatePrescription'])->name('pharmacien.ordonnances.valider');
    Route::patch('/pharmacien/ordonnances/{id}/refuser', [PharmacienController::class, 'rejectPrescription'])->name('pharmacien.ordonnances.refuser');
    Route::get('/pharmacien/ordonnances/{id}/fichier', [PharmacienController::class, 'prescriptionFile'])->name('pharmacien.ordonnances.fichier');
    Route::get('/pharmacien/stock', [PharmacienController::class, 'stock'])->name('pharmacien.stock');
    Route::post('/pharmacien/stock/{id}/reapprovisionner', [PharmacienController::class, 'restock'])->name('pharmacien.stock.reapprovisionner');
});
Route::middleware(['gateway.auth', 'gateway.role:livreur'])->group(function () {
    Route::get('/livreur', [CourierController::class, 'dashboard'])->name('livreur.dashboard');
    Route::patch('/livreur/missions/{id}/livree', [CourierController::class, 'completeMission'])->name('livreur.missions.complete');
});
Route::get('/admin', [GatewayController::class, 'adminDashboard'])
    ->middleware(['gateway.auth', 'gateway.role:admin'])
    ->name('admin.dashboard');
Route::middleware(['gateway.auth', 'gateway.role:admin'])->group(function () {
    Route::get('/admin/catalogue', [CatalogueAdminController::class, 'index'])->name('admin.catalogue');
    Route::post('/admin/catalogue/categories', [CatalogueAdminController::class, 'storeCategory'])->name('admin.catalogue.categories.store');
    Route::put('/admin/catalogue/categories/{id}', [CatalogueAdminController::class, 'updateCategory'])->name('admin.catalogue.categories.update');
    Route::post('/admin/catalogue/produits', [CatalogueAdminController::class, 'storeProduct'])->name('admin.catalogue.produits.store');
    Route::put('/admin/catalogue/produits/{id}', [CatalogueAdminController::class, 'updateProduct'])->name('admin.catalogue.produits.update');
    Route::delete('/admin/catalogue/produits/{id}', [CatalogueAdminController::class, 'destroyProduct'])->name('admin.catalogue.produits.destroy');
    Route::get('/admin/utilisateurs', [AdminController::class, 'users'])->name('admin.utilisateurs');
    Route::post('/admin/utilisateurs', [AdminController::class, 'createUser'])->name('admin.utilisateurs.store');
    Route::post('/admin/utilisateurs/{id}/invitation', [AdminController::class, 'resendInvitation'])->name('admin.utilisateurs.invitation');
    Route::patch('/admin/utilisateurs/{id}', [AdminController::class, 'updateUser'])->name('admin.utilisateurs.update');
    Route::get('/admin/commandes', [AdminController::class, 'orders'])->name('admin.commandes');
    Route::patch('/admin/commandes/{id}/statut', [AdminController::class, 'updateOrderStatus'])->name('admin.commandes.statut');
    Route::patch('/admin/commandes/{id}/livreur', [AdminController::class, 'assignCourier'])->name('admin.commandes.livreur');
});
