<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Models\PurchaseOrder;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process received purchase order and update ingredient stock
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processPurchaseOrderReceipt(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_order_id' => 'required|integer|exists:purchase_orders,purchase_order_id',
        ]);

        try {
            $results = $this->inventoryService->addStockFromPurchaseOrder(
                $request->purchase_order_id
            );

            $successCount = collect($results['results'])->where('success', true)->count();
            $totalCount = count($results['results']);

            return response()->json([
                'success' => true,
                'message' => "Processed {$successCount}/{$totalCount} purchase order items from {$results['po_number']} successfully",
                'data' => $results,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process purchase order receipt: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process purchase order receipt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process dish sale and reduce ingredient stock
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processDishSale(Request $request): JsonResponse
    {
        $request->validate([
            'dish_id' => 'required|integer|exists:dishes,dish_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $results = $this->inventoryService->subtractStockFromDishSale(
                $request->dish_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully processed sale of {$request->quantity} {$results['dish_name']}(s). Updated {$results['ingredients_processed']} ingredients.",
                'data' => $results,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process dish sale: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process dish sale',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check stock availability for a dish
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkStockAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'dish_id' => 'required|integer|exists:dishes,dish_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $availability = $this->inventoryService->checkStockAvailability(
                $request->dish_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'data' => $availability,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to check stock availability: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check stock availability',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get ingredients with low stock levels
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getLowStockIngredients(Request $request): JsonResponse
    {
        try {
            $restaurantId = $request->query('restaurant_id');
            
            $lowStockIngredients = $this->inventoryService->getLowStockIngredients($restaurantId);

            return response()->json([
                'success' => true,
                'data' => $lowStockIngredients->map(function ($ingredient) {
                    return [
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'current_stock' => $ingredient->current_stock,
                        'reorder_level' => $ingredient->reorder_level,
                        'base_unit' => $ingredient->base_unit,
                        'suppliers' => $ingredient->suppliers->map(function ($supplier) {
                            return [
                                'supplier_id' => $supplier->supplier_id,
                                'name' => $supplier->name,
                                'package_unit' => $supplier->pivot->package_unit,
                                'package_quantity' => $supplier->pivot->package_quantity,
                                'package_price' => $supplier->pivot->package_price,
                                'lead_time_days' => $supplier->pivot->lead_time_days,
                            ];
                        }),
                    ];
                }),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get low stock ingredients: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get low stock ingredients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate ingredient cost for a dish
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateIngredientCost(Request $request): JsonResponse
    {
        $request->validate([
            'dish_id' => 'required|integer|exists:dishes,dish_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $costAnalysis = $this->inventoryService->calculateIngredientCost(
                $request->dish_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'data' => $costAnalysis,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to calculate ingredient cost: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate ingredient cost',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch process multiple dish sales (useful for processing complete orders)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processBatchDishSales(Request $request): JsonResponse
    {
        $request->validate([
            'dishes' => 'required|array',
            'dishes.*.dish_id' => 'required|integer|exists:dishes,dish_id',
            'dishes.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $allResults = [];
            $errors = [];

            foreach ($request->dishes as $dishOrder) {
                try {
                    $result = $this->inventoryService->subtractStockFromDishSale(
                        $dishOrder['dish_id'],
                        $dishOrder['quantity']
                    );
                    $allResults[] = $result;

                } catch (Exception $e) {
                    $errors[] = [
                        'dish_id' => $dishOrder['dish_id'],
                        'quantity' => $dishOrder['quantity'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $response = [
                'success' => empty($errors),
                'processed_dishes' => count($allResults),
                'failed_dishes' => count($errors),
                'results' => $allResults,
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            return response()->json($response, empty($errors) ? 200 : 207); // 207 Multi-Status for partial success

        } catch (Exception $e) {
            Log::error('Failed to process batch dish sales: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process batch dish sales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}