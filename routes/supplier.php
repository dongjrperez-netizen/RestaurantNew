<?php

use App\Http\Controllers\Supplier\AuthController;
use App\Http\Controllers\Supplier\DashboardController;
use App\Http\Controllers\Supplier\IngredientOfferController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supplier Routes
|--------------------------------------------------------------------------
|
| Here is where you can register supplier routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "supplier" middleware group. Now create something great!
|
*/

// Guest Supplier Routes
Route::middleware('guest:supplier')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('supplier.login');
    Route::post('/login', [AuthController::class, 'login'])->name('supplier.login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('supplier.register');
    Route::post('/register', [AuthController::class, 'register'])->name('supplier.register.post');
});

// Authenticated Supplier Routes
Route::middleware('auth:supplier')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('supplier.logout');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('supplier.dashboard');
    
    // Ingredient Offers Management
    Route::prefix('ingredients')->name('supplier.ingredients.')->group(function () {
        Route::get('/', [IngredientOfferController::class, 'index'])->name('index');
        Route::get('/create', [IngredientOfferController::class, 'create'])->name('create');
        Route::post('/', [IngredientOfferController::class, 'store'])->name('store');
        Route::get('/{ingredient}/edit', [IngredientOfferController::class, 'edit'])->name('edit');
        Route::put('/{ingredient}', [IngredientOfferController::class, 'update'])->name('update');
        Route::delete('/{ingredient}', [IngredientOfferController::class, 'destroy'])->name('destroy');
    });
});