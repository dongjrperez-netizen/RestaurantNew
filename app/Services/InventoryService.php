<?php

namespace App\Services;

use App\Models\Dish;
use App\Models\Ingredients;
use App\Models\PurchaseOrder;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Add stock to ingredients based on received purchase order
     *
     * @return array Results with success/failure status
     *
     * @throws Exception
     */
    public function addStockFromPurchaseOrder(int $purchaseOrderId): array
    {
        return DB::transaction(function () use ($purchaseOrderId) {
            $purchaseOrder = PurchaseOrder::with(['items.ingredient', 'supplier'])
                ->findOrFail($purchaseOrderId);

            $results = [];
            $totalItemsProcessed = 0;

            foreach ($purchaseOrder->items as $orderItem) {
                try {
                    if (! $orderItem->ingredient) {
                        $results[] = [
                            'item_id' => $orderItem->purchase_order_item_id,
                            'success' => false,
                            'message' => 'Ingredient not found',
                        ];

                        continue;
                    }

                    if ($orderItem->received_quantity <= 0) {
                        $results[] = [
                            'item_id' => $orderItem->purchase_order_item_id,
                            'success' => false,
                            'message' => 'No received quantity to process',
                        ];

                        continue;
                    }

                    $baseUnitsToAdd = $orderItem->getBaseUnitsReceived();
                    $ingredient = $orderItem->ingredient;
                    $oldStock = $ingredient->current_stock;

                    $ingredient->increaseStock($baseUnitsToAdd);

                    Log::info("Stock updated for ingredient {$ingredient->ingredient_name}: {$oldStock} + {$baseUnitsToAdd} = {$ingredient->fresh()->current_stock}");

                    $results[] = [
                        'item_id' => $orderItem->purchase_order_item_id,
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'success' => true,
                        'packages_received' => $orderItem->received_quantity,
                        'base_units_added' => $baseUnitsToAdd,
                        'old_stock' => $oldStock,
                        'new_stock' => $ingredient->fresh()->current_stock,
                        'base_unit' => $ingredient->base_unit,
                    ];

                    $totalItemsProcessed++;

                } catch (Exception $e) {
                    Log::error("Failed to update stock for purchase order item {$orderItem->purchase_order_item_id}: ".$e->getMessage());

                    $results[] = [
                        'item_id' => $orderItem->purchase_order_item_id,
                        'success' => false,
                        'message' => 'Error updating stock: '.$e->getMessage(),
                    ];
                }
            }

            Log::info('Stock added from purchase order', [
                'purchase_order_id' => $purchaseOrderId,
                'po_number' => $purchaseOrder->po_number,
                'supplier' => $purchaseOrder->supplier->name ?? 'Unknown',
                'items_processed' => $totalItemsProcessed,
                'total_items' => count($purchaseOrder->items),
            ]);

            return [
                'purchase_order_id' => $purchaseOrderId,
                'po_number' => $purchaseOrder->po_number,
                'supplier' => $purchaseOrder->supplier->name ?? 'Unknown',
                'items_processed' => $totalItemsProcessed,
                'total_items' => count($purchaseOrder->items),
                'results' => $results,
            ];
        });
    }

    /**
     * Subtract stock from ingredients when a dish is sold
     *
     * @return array Results with success/failure status
     *
     * @throws Exception
     */
    public function subtractStockFromDishSale(int $dishId, int $quantitySold): array
    {
        if ($quantitySold <= 0) {
            throw new Exception('Quantity sold must be greater than 0');
        }

        return DB::transaction(function () use ($dishId, $quantitySold) {
            $dish = Dish::with(['dishIngredients.ingredient'])->findOrFail($dishId);

            if (! $dish->hasAvailableStock($quantitySold)) {
                $insufficientStock = [];
                foreach ($dish->dishIngredients as $dishIngredient) {
                    $requiredQuantity = $dishIngredient->quantity_needed * $quantitySold;
                    if ($dishIngredient->ingredient->current_stock < $requiredQuantity) {
                        $insufficientStock[] = [
                            'ingredient_id' => $dishIngredient->ingredient->ingredient_id,
                            'ingredient_name' => $dishIngredient->ingredient->ingredient_name,
                            'required' => $requiredQuantity,
                            'available' => $dishIngredient->ingredient->current_stock,
                            'shortage' => $requiredQuantity - $dishIngredient->ingredient->current_stock,
                        ];
                    }
                }
                throw new Exception('Insufficient stock for dish: '.$dish->dish_name.'. Details: '.json_encode($insufficientStock));
            }

            $results = [];
            $totalIngredientsProcessed = 0;

            foreach ($dish->dishIngredients as $dishIngredient) {
                try {
                    $ingredient = $dishIngredient->ingredient;
                    $requiredQuantity = $dishIngredient->quantity_needed * $quantitySold;
                    $oldStock = $ingredient->current_stock;

                    $ingredient->decreaseStock($requiredQuantity);

                    Log::info("Stock reduced for ingredient {$ingredient->ingredient_name}: {$oldStock} - {$requiredQuantity} = {$ingredient->fresh()->current_stock}");

                    $results[] = [
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'success' => true,
                        'quantity_per_dish' => $dishIngredient->quantity_needed,
                        'total_quantity_used' => $requiredQuantity,
                        'old_stock' => $oldStock,
                        'new_stock' => $ingredient->fresh()->current_stock,
                        'base_unit' => $ingredient->base_unit,
                    ];

                    if ($ingredient->fresh()->current_stock <= $ingredient->reorder_level) {
                        Log::warning("Ingredient {$ingredient->ingredient_name} is at or below reorder level. Current: {$ingredient->fresh()->current_stock}, Reorder Level: {$ingredient->reorder_level}");
                    }

                    $totalIngredientsProcessed++;

                } catch (Exception $e) {
                    Log::error("Failed to reduce stock for ingredient {$ingredient->ingredient_id}: ".$e->getMessage());
                    throw $e;
                }
            }

            Log::info('Stock subtracted for dish sale', [
                'dish_id' => $dishId,
                'dish_name' => $dish->dish_name,
                'quantity_sold' => $quantitySold,
                'ingredients_processed' => $totalIngredientsProcessed,
            ]);

            return [
                'dish_id' => $dishId,
                'dish_name' => $dish->dish_name,
                'quantity_sold' => $quantitySold,
                'ingredients_processed' => $totalIngredientsProcessed,
                'ingredients_updated' => $results,
            ];
        });
    }

    /**
     * Check if there's sufficient stock to fulfill a dish order
     */
    public function checkStockAvailability(int $dishId, int $quantity): array
    {
        $dish = Dish::with(['dishIngredients.ingredient'])->findOrFail($dishId);

        $availability = [];
        $canFulfill = true;

        foreach ($dish->dishIngredients as $dishIngredient) {
            $ingredient = $dishIngredient->ingredient;
            $requiredQuantity = $dishIngredient->quantity_needed * $quantity;
            $isAvailable = $ingredient->current_stock >= $requiredQuantity;

            if (! $isAvailable) {
                $canFulfill = false;
            }

            $availability[] = [
                'ingredient_id' => $ingredient->ingredient_id,
                'ingredient_name' => $ingredient->ingredient_name,
                'base_unit' => $ingredient->base_unit,
                'required_quantity' => $requiredQuantity,
                'current_stock' => $ingredient->current_stock,
                'is_available' => $isAvailable,
                'shortage' => $isAvailable ? 0 : $requiredQuantity - $ingredient->current_stock,
            ];
        }

        return [
            'dish_id' => $dishId,
            'dish_name' => $dish->dish_name,
            'quantity_requested' => $quantity,
            'can_fulfill' => $canFulfill,
            'ingredients' => $availability,
        ];
    }

    /**
     * Get ingredients that are at or below their reorder level
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockIngredients(?int $restaurantId = null)
    {
        $query = Ingredients::whereRaw('current_stock <= reorder_level')
            ->with(['suppliers' => function ($query) {
                $query->where('is_active', true);
            }]);

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        return $query->orderBy('current_stock', 'asc')->get();
    }

    /**
     * Calculate the total cost of ingredients used for a dish
     */
    public function calculateIngredientCost(int $dishId, int $quantity): array
    {
        $dish = Dish::with(['dishIngredients.ingredient.suppliers'])->findOrFail($dishId);

        $totalCost = 0;
        $ingredientCosts = [];

        foreach ($dish->dishIngredients as $dishIngredient) {
            $ingredient = $dishIngredient->ingredient;
            $requiredQuantity = $dishIngredient->quantity_needed * $quantity;

            $activeSupplier = $ingredient->suppliers()
                ->where('is_active', true)
                ->orderBy('package_price', 'asc')
                ->first();

            $cost = 0;
            if ($activeSupplier) {
                $costPerBaseUnit = $activeSupplier->pivot->package_price / $activeSupplier->pivot->package_quantity;
                $cost = $requiredQuantity * $costPerBaseUnit;
                $totalCost += $cost;
            }

            $ingredientCosts[] = [
                'ingredient_id' => $ingredient->ingredient_id,
                'ingredient_name' => $ingredient->ingredient_name,
                'quantity_needed' => $requiredQuantity,
                'base_unit' => $ingredient->base_unit,
                'cost_per_unit' => $activeSupplier ? $costPerBaseUnit : null,
                'total_cost' => $cost,
                'supplier_name' => $activeSupplier?->name ?? 'No active supplier',
            ];
        }

        return [
            'dish_id' => $dishId,
            'dish_name' => $dish->dish_name,
            'quantity' => $quantity,
            'total_ingredient_cost' => round($totalCost, 2),
            'ingredients' => $ingredientCosts,
        ];
    }
}
