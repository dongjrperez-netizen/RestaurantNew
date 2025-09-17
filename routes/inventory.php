<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockInventoryController;
use App\Http\Controllers\KitchenInventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'verified'])->prefix('inventory')->group(function () {
    
    Route::post('/purchase-order/receipt', [InventoryController::class, 'processPurchaseOrderReceipt'])
        ->name('inventory.purchase-order.receipt');
    
    Route::post('/dish/sale', [InventoryController::class, 'processDishSale'])
        ->name('inventory.dish.sale');
    
    Route::post('/dish/batch-sale', [InventoryController::class, 'processBatchDishSales'])
        ->name('inventory.dish.batch-sale');
    
    Route::get('/dish/stock-check', [InventoryController::class, 'checkStockAvailability'])
        ->name('inventory.dish.stock-check');
    
    Route::get('/ingredients/low-stock', [InventoryController::class, 'getLowStockIngredients'])
        ->name('inventory.ingredients.low-stock');
    
    Route::get('/dish/ingredient-cost', [InventoryController::class, 'calculateIngredientCost'])
        ->name('inventory.dish.ingredient-cost');
});

// Stock Inventory Routes
Route::middleware(['auth:web', 'verified', 'check.subscription'])->group(function () {
    // Stock Inventory Dashboard
    Route::get('/stock-inventory/dashboard', [StockInventoryController::class, 'dashboard'])->name('stock-inventory.dashboard');

    // Stock Inventory CRUD
    Route::resource('stock-inventory', StockInventoryController::class);

    // Stock Inventory Transfer to Kitchen
    Route::post('/stock-inventory/{stockInventory}/transfer', [StockInventoryController::class, 'transfer'])
        ->name('stock-inventory.transfer');

    // Expiring Stock
    Route::get('/stock-inventory-expiring', [StockInventoryController::class, 'expiringSoon'])
        ->name('stock-inventory.expiring');
});

// Kitchen Inventory Routes
Route::middleware(['auth:web', 'verified', 'check.subscription'])->group(function () {
    // Kitchen Inventory Dashboard
    Route::get('/kitchen-inventory/dashboard', [KitchenInventoryController::class, 'dashboard'])->name('kitchen-inventory.dashboard');

    // Kitchen Inventory CRUD
    Route::get('/kitchen-inventory', [KitchenInventoryController::class, 'index'])->name('kitchen-inventory.index');
    Route::get('/kitchen-inventory/{kitchenInventory}', [KitchenInventoryController::class, 'show'])->name('kitchen-inventory.show');

    // Kitchen Inventory Actions
    Route::post('/kitchen-inventory/{kitchenInventory}/use', [KitchenInventoryController::class, 'useStock'])
        ->name('kitchen-inventory.use');
    Route::post('/kitchen-inventory/{kitchenInventory}/return', [KitchenInventoryController::class, 'returnStock'])
        ->name('kitchen-inventory.return');
    Route::post('/kitchen-inventory/{kitchenInventory}/expire', [KitchenInventoryController::class, 'markExpired'])
        ->name('kitchen-inventory.expire');

    // Batch Actions
    Route::post('/kitchen-inventory/batch/expire', [KitchenInventoryController::class, 'batchMarkExpired'])
        ->name('kitchen-inventory.batch-expire');

    // Expiring Kitchen Stock
    Route::get('/kitchen-inventory-expiring', [KitchenInventoryController::class, 'expiringSoon'])
        ->name('kitchen-inventory.expiring');

    // Usage Reports
    Route::get('/kitchen-inventory/reports/usage', [KitchenInventoryController::class, 'usageReport'])
        ->name('kitchen-inventory.usage-report');
});