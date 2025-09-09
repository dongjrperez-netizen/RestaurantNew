<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\SubscriptionpackageController;
use App\Http\Controllers\UserSubscriptionController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AccountUpdateController;
use App\Http\Controllers\RenewSubscriptionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierBillController;
use App\Http\Controllers\SupplierPaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'check.subscription'])->name('dashboard');



Route::get('/admin/login', [AdministratorController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdministratorController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdministratorController::class, 'logout'])->name('admin.logout');

Route::middleware('admin.auth')->group(function () {
    // Admin Dashboard
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    // User Management
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::post('/admin/users/{id}/status', [AdminUserController::class, 'updateStatus'])->name('admin.users.status');
    Route::post('/admin/users/{id}/toggle-email', [AdminUserController::class, 'toggleEmailVerification'])->name('admin.users.toggle-email');
    Route::post('/admin/users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    
    // Subscription Management
    Route::get('/admin/subscriptions', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
    Route::get('/admin/subscriptions/{id}', [AdminSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
    Route::post('/admin/subscriptions/{id}/extend', [AdminSubscriptionController::class, 'extend'])->name('admin.subscriptions.extend');
    Route::post('/admin/subscriptions/{id}/suspend', [AdminSubscriptionController::class, 'suspend'])->name('admin.subscriptions.suspend');
    Route::post('/admin/subscriptions/{id}/activate', [AdminSubscriptionController::class, 'activate'])->name('admin.subscriptions.activate');
    
    // System Settings
    Route::get('/admin/settings', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings/admins', [AdminSettingsController::class, 'createAdmin'])->name('admin.settings.admins.create');
    Route::delete('/admin/settings/admins/{id}', [AdminSettingsController::class, 'deleteAdmin'])->name('admin.settings.admins.delete');
    Route::post('/admin/settings/subscription-plans', [AdminSettingsController::class, 'createSubscriptionPlan'])->name('admin.settings.plans.create');
    Route::put('/admin/settings/subscription-plans/{id}', [AdminSettingsController::class, 'updateSubscriptionPlan'])->name('admin.settings.plans.update');
    Route::delete('/admin/settings/subscription-plans/{id}', [AdminSettingsController::class, 'deleteSubscriptionPlan'])->name('admin.settings.plans.delete');
});


Route::middleware(['auth', 'verified', 'check.subscription'])->group(function(){
    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status');
});

Route::get('/register/documents', [RegisteredUserController::class, 'showDocumentUpload'])->middleware('auth')->name('register.documents');
Route::post('/register/documents', [RegisteredUserController::class, 'store_doc'])->middleware('auth')->name('register.documents.store');


Route::middleware(['auth', 'verified', 'check.subscription'])->group(function(){
    Route::get('/product', [ProductController::class, 'index'])->name('product.index');
    Route::post('/product', [ProductController::class, 'store'])->name('product.store');
});

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function(){
    Route::get('/kitchen', [KitchenController:: class, 'Index'])->name('kitchen.index');

});

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function(){
    Route::get('/supplier', [SuppliersController:: class, 'Index'])->name('supplier.index');
    Route::get('/reorder', [SuppliersController:: class, 'Reorder'])->name('supplier.reorder');
    Route::get('/orderedRequested', [SuppliersController:: class, 'OrderedRequested'])->name('supplier.OrderedRequested');
  
});


// Document approval routes (should be admin-only)
Route::middleware('admin.auth')->group(function(){
    Route::get('/request', [DocumentController::class, 'index'])->name('request');
    Route::post('/admin/accounts/{restaurant}/approve', [DocumentController::class, 'approve'])->name('admin.accounts.approve');
    Route::post('/admin/accounts/{restaurant}/reject', [DocumentController::class, 'reject'])->name('admin.accounts.reject');
});

Route::get('/subscriptions', [SubscriptionpackageController::class, 'index'])->name('subscriptions.index');
Route::post('/subscriptions/create', [SubscriptionpackageController::class, 'create'])->name('subscriptions.create');
Route::post('/subscriptions/free-trial', [SubscriptionpackageController::class, 'createFreeTrial'])->name('subscriptions.free-trial');
Route::get('/subscriptions/success', [SubscriptionpackageController::class, 'success'])->name('subscriptions.success');
Route::get('/subscriptions/cancel', [SubscriptionpackageController::class, 'cancel'])->name('subscriptions.cancel');


//inventory and stock in routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/stock-list', [StockInController::class, 'index'])->name('stock-in.index');
    Route::get('/inventory/stock-in', [StockInController::class, 'create'])->name('stock-in.create');
    Route::post('/inventory/stock-in', [StockInController::class, 'store'])->name('stock-in.store');
    Route::post('/ingredients/store-quick', [StockInController::class, 'storeIngredient'])->name('ingredients.store.quick');
    Route::get('/api/purchase-orders/{purchaseOrderId}/details', [StockInController::class, 'getPurchaseOrderDetails'])->name('purchase-orders.api-details');
});

Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/account/update', [AccountUpdateController::class, 'show'])->name('account.update.show');
    Route::post('/account/update', [AccountUpdateController::class, 'update'])->name('account.update');
    Route::get('/api/account/user', [AccountUpdateController::class, 'fetchUser']);
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/subscriptions/renew', [RenewSubscriptionController::class, 'show'])->name('subscriptions.renew');
    Route::post('/subscriptions/renew', [RenewSubscriptionController::class, 'renew'])->name('subscriptions.renew.process');
    Route::get('/subscriptions/renew/success', [RenewSubscriptionController::class, 'renewSuccess'])->name('subscriptions.renew.success');
    Route::get('/subscriptions/renew/cancel', [RenewSubscriptionController::class, 'renewCancel'])->name('subscriptions.renew.cancel');
});

// Supplier Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers/{id}/details', [SupplierController::class, 'getSupplierDetails'])->name('suppliers.details');
    Route::post('/suppliers/{id}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
    Route::post('/suppliers/{id}/send-invitation', [SupplierController::class, 'sendInvitation'])->name('suppliers.send-invitation');
});

// Purchase Order Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::get('/purchase-orders/pending-approvals', [PurchaseOrderController::class, 'pendingApprovals'])->name('purchase-orders.pending-approvals');
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'processReceive'])->name('purchase-orders.process-receive');
});

// Supplier Bills Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::resource('bills', SupplierBillController::class);
    Route::post('/bills/from-purchase-order/{purchaseOrderId}', [SupplierBillController::class, 'createFromPurchaseOrder'])->name('bills.from-purchase-order');
    Route::get('/bills/{id}/download', [SupplierBillController::class, 'downloadAttachment'])->name('bills.download');
    Route::post('/bills/mark-overdue', [SupplierBillController::class, 'markOverdue'])->name('bills.mark-overdue');
});

// Supplier Payments Management Routes
Route::middleware(['auth', 'verified', 'check.subscription'])->group(function () {
    Route::resource('payments', SupplierPaymentController::class);
    Route::get('/payments/create/{billId}', [SupplierPaymentController::class, 'create'])->name('payments.create-for-bill');
    Route::post('/payments/{id}/cancel', [SupplierPaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/api/bills/{id}/details', [SupplierPaymentController::class, 'getBillDetails'])->name('bills.api-details');
});




require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/inventory.php';
require __DIR__.'/billing.php';
