<?php

use App\Http\Controllers\InventoryController;
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