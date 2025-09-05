<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\SubscriptionpackageController;
use App\Http\Controllers\UserSubscriptionController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AccountUpdateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('administrator', function () {
    return Inertia::render('Administrator');
})->middleware(['auth', 'verified'])->name('administrator');

Route::get('/admin/login', [AdministratorController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdministratorController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdministratorController::class, 'logout'])->name('admin.logout');

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Administrator');
    })->name('admin.dashboard');
});


Route::middleware('auth', 'verified')->group(function(){
    Route::get('/employees', [EmployeeController:: class, 'Index'])->name('employees.index');

});

Route::get('/register/documents', [RegisteredUserController::class, 'showDocumentUpload'])->middleware('auth')->name('register.documents');
Route::post('/register/documents', [RegisteredUserController::class, 'store_doc'])->middleware('auth')->name('register.documents.store');


Route::middleware('auth', 'verified')->group(function(){
    Route::get('/product', [ProductController::class, 'index'])->name('product.index');
    Route::post('/product', [ProductController::class, 'store'])->name('product.store');
});

Route::middleware('auth', 'verified')->group(function(){
    Route::get('/kitchen', [KitchenController:: class, 'Index'])->name('kitchen.index');

});

Route::middleware('auth', 'verified')->group(function(){
    Route::get('/supplier', [SuppliersController:: class, 'Index'])->name('supplier.index');
    Route::get('/reorder', [SuppliersController:: class, 'Reorder'])->name('supplier.reorder');
    Route::get('/orderedRequested', [SuppliersController:: class, 'OrderedRequested'])->name('supplier.OrderedRequested');
  
});


Route::middleware('auth', 'verified')->group(function(){
    Route::get('/request', [DocumentController::class, 'index'])->name('request');
    Route::post('/admin/accounts/{restaurant}/approve', [DocumentController::class, 'approve'])->name('admin.accounts.approve');
    Route::post('/admin/accounts/{restaurant}/reject', [DocumentController::class, 'reject'])->name('admin.accounts.reject');
});

Route::get('/subscriptions', [SubscriptionpackageController::class, 'index'])->name('subscriptions.index');
Route::post('/subscriptions/create', [SubscriptionpackageController::class, 'create'])->name('subscriptions.create');
Route::get('/subscriptions/success', [SubscriptionpackageController::class, 'success'])->name('subscriptions.success');
Route::get('/subscriptions/cancel', [SubscriptionpackageController::class, 'cancel'])->name('subscriptions.cancel');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inventory/stock-in', [StockInController::class, 'create'])->name('stock-in.create');
    Route::post('/inventory/stock-in', [StockInController::class, 'store'])->name('stock-in.store');
    Route::post('/ingredients/store-quick', [StockInController::class, 'storeIngredient'])->name('ingredients.store.quick');
    Route::get('/account/update', [AccountUpdateController::class, 'show'])->name('account.update');
    Route::post('/account/update', [AccountUpdateController::class, 'update']);
    Route::get('/api/account/user', [AccountUpdateController::class, 'fetchUser']);
});
Route::get('accountupadate', function () {
    return Inertia::render('AccountUpdate');
})->middleware(['auth', 'verified'])->name('accountupdate');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
