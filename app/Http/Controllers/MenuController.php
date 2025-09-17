<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\MenuCategory;
use App\Models\Ingredients;
use App\Models\DishPricing;
use App\Models\DishCost;
use App\Models\DishIngredient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    /**
     * Display a listing of menu items.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access the menu.');
        }
        
        if (!$user->restaurantData) {
            return redirect()->route('dashboard')->with('error', 'Restaurant data not found. Please complete your profile.');
        }
        
        $restaurantId = $user->restaurantData->id;
        
        $dishes = Dish::with(['category', 'pricing', 'ingredients'])
            ->forRestaurant($restaurantId)
            ->when($request->category_id, function($query, $categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->search, function($query, $search) {
                return $query->where('dish_name', 'like', "%{$search}%");
            })
            ->orderBy('dish_name')
            ->get();

        $categories = MenuCategory::forRestaurant($restaurantId)
            ->active()
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Menu/Index', [
            'dishes' => $dishes,
            'categories' => $categories,
            'filters' => [
                'category_id' => $request->category_id,
                'status' => $request->status,
                'search' => $request->search,
            ]
        ]);
    }

    /**
     * Show the form for creating a new dish.
     */
    public function create()
    {
        $restaurantId = auth()->user()->restaurantData->id;
        
        $categories = MenuCategory::forRestaurant($restaurantId)
            ->active()
            ->orderBy('category_name')
            ->get();
            
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->with('suppliers')
            ->orderBy('ingredient_name')
            ->get()
            ->map(function($ingredient) {
                // Get the first supplier's package price or default to 0
                $costPerUnit = 0;
                if ($ingredient->suppliers->isNotEmpty()) {
                    $firstSupplier = $ingredient->suppliers->first();
                    if ($firstSupplier->pivot && $firstSupplier->pivot->package_price && $firstSupplier->pivot->package_quantity) {
                        $costPerUnit = $firstSupplier->pivot->package_price / $firstSupplier->pivot->package_quantity;
                    }
                }
                
                return [
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'unit_of_measure' => $ingredient->base_unit,
                    'cost_per_unit' => $costPerUnit,
                    'current_stock' => $ingredient->current_stock,
                ];
            });

        return Inertia::render('Menu/Create', [
            'categories' => $categories,
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * Store a newly created dish in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dish_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:menu_categories,category_id',
            'preparation_time' => 'nullable|integer|min:1',
            'serving_size' => 'nullable|numeric|min:0',
            'serving_unit' => 'nullable|string|max:20',
            'image_url' => 'nullable|url|max:500',
            'calories' => 'nullable|integer|min:0',
            'allergens' => 'nullable|array',
            'dietary_tags' => 'nullable|array',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'nullable|exists:ingredients,ingredient_id',
            'ingredients.*.ingredient_name' => 'required|string|max:255',
            'ingredients.*.quantity' => 'required|numeric|min:0',
            'ingredients.*.unit' => 'required|string|max:20',
            'ingredients.*.cost_per_unit' => 'nullable|numeric|min:0',
            'ingredients.*.is_optional' => 'boolean',
            'ingredients.*.preparation_note' => 'nullable|string',
            'pricing' => 'required|array',
            'pricing.*.price_type' => 'required|in:dine_in,takeout',
            'pricing.*.base_price' => 'required|numeric|min:0',
            'pricing.*.min_profit_margin' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function() use ($validated) {
            $restaurantId = auth()->user()->restaurantData->id;
            
            // Create the dish
            $dish = Dish::create([
                'restaurant_id' => $restaurantId,
                'category_id' => $validated['category_id'],
                'dish_name' => $validated['dish_name'],
                'description' => $validated['description'],
                'preparation_time' => $validated['preparation_time'],
                'serving_size' => $validated['serving_size'],
                'serving_unit' => $validated['serving_unit'],
                'image_url' => $validated['image_url'],
                'calories' => $validated['calories'],
                'allergens' => $validated['allergens'],
                'dietary_tags' => $validated['dietary_tags'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Add ingredients
            foreach ($validated['ingredients'] as $ingredient) {
                DishIngredient::create([
                    'dish_id' => $dish->dish_id,
                    'ingredient_id' => $ingredient['ingredient_id'] ?? null,
                    'ingredient_name' => $ingredient['ingredient_name'],
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                    'cost_per_unit' => $ingredient['cost_per_unit'] ?? 0,
                    'is_optional' => $ingredient['is_optional'] ?? false,
                    'preparation_note' => $ingredient['preparation_note'],
                ]);
            }

            // Add pricing
            foreach ($validated['pricing'] as $pricing) {
                DishPricing::create([
                    'dish_id' => $dish->dish_id,
                    'price_type' => $pricing['price_type'],
                    'base_price' => $pricing['base_price'],
                    'min_profit_margin' => $pricing['min_profit_margin'] ?? 20,
                ]);
            }

            // Calculate initial cost
            $this->calculateDishCost($dish);
        });

        return redirect()->route('menu.index')
            ->with('success', 'Dish created successfully!');
    }

    /**
     * Display the specified dish.
     */
    public function show(Dish $dish)
    {
        $dish->load(['category', 'ingredients', 'pricing', 'costs' => function($query) {
            $query->latest('calculated_at')->limit(5);
        }]);

        return Inertia::render('Menu/Show', [
            'dish' => $dish,
        ]);
    }

    /**
     * Show the form for editing the specified dish.
     */
    public function edit(Dish $dish)
    {
        $restaurantId = auth()->user()->restaurantData->id;
        
        $dish->load(['category', 'ingredients', 'pricing']);
        
        $categories = MenuCategory::forRestaurant($restaurantId)
            ->active()
            ->orderBy('category_name')
            ->get();
            
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->with('suppliers')
            ->orderBy('ingredient_name')
            ->get()
            ->map(function($ingredient) {
                // Get the first supplier's package price or default to 0
                $costPerUnit = 0;
                if ($ingredient->suppliers->isNotEmpty()) {
                    $firstSupplier = $ingredient->suppliers->first();
                    if ($firstSupplier->pivot && $firstSupplier->pivot->package_price && $firstSupplier->pivot->package_quantity) {
                        $costPerUnit = $firstSupplier->pivot->package_price / $firstSupplier->pivot->package_quantity;
                    }
                }
                
                return [
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'unit_of_measure' => $ingredient->base_unit,
                    'cost_per_unit' => $costPerUnit,
                    'current_stock' => $ingredient->current_stock,
                ];
            });

        return Inertia::render('Menu/Edit', [
            'dish' => $dish,
            'categories' => $categories,
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * Update the specified dish in storage.
     */
    public function update(Request $request, Dish $dish)
    {
        $validated = $request->validate([
            'dish_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:menu_categories,category_id',
            'preparation_time' => 'nullable|integer|min:1',
            'serving_size' => 'nullable|numeric|min:0',
            'serving_unit' => 'nullable|string|max:20',
            'image_url' => 'nullable|url|max:500',
            'calories' => 'nullable|integer|min:0',
            'allergens' => 'nullable|array',
            'dietary_tags' => 'nullable|array',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'ingredients.*.quantity' => 'required|numeric|min:0',
            'ingredients.*.unit' => 'required|string|max:20',
            'ingredients.*.is_optional' => 'boolean',
            'ingredients.*.preparation_note' => 'nullable|string',
            'pricing' => 'nullable|array',
            'pricing.*.pricing_id' => 'nullable|exists:dish_pricing,pricing_id',
            'pricing.*.price_type' => 'required|in:dine_in,takeout',
            'pricing.*.base_price' => 'required|numeric|min:0',
            'pricing.*.promotional_price' => 'nullable|numeric|min:0',
            'pricing.*.promo_start_date' => 'nullable|date',
            'pricing.*.promo_end_date' => 'nullable|date|after_or_equal:promo_start_date',
            'pricing.*.min_profit_margin' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function() use ($validated, $dish) {
            // Update basic dish information
            $dish->update([
                'dish_name' => $validated['dish_name'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'preparation_time' => $validated['preparation_time'],
                'serving_size' => $validated['serving_size'],
                'serving_unit' => $validated['serving_unit'],
                'image_url' => $validated['image_url'],
                'calories' => $validated['calories'],
                'allergens' => $validated['allergens'],
                'dietary_tags' => $validated['dietary_tags'],
                'updated_by' => auth()->id(),
            ]);

            // Update ingredients if provided
            if (isset($validated['ingredients'])) {
                // Remove existing ingredients
                $dish->ingredients()->detach();
                
                // Add new ingredients
                foreach ($validated['ingredients'] as $ingredient) {
                    $dish->ingredients()->attach($ingredient['ingredient_id'], [
                        'quantity' => $ingredient['quantity'],
                        'unit' => $ingredient['unit'],
                        'is_optional' => $ingredient['is_optional'] ?? false,
                        'preparation_note' => $ingredient['preparation_note'],
                    ]);
                }
            }

            // Update pricing if provided
            if (isset($validated['pricing'])) {
                foreach ($validated['pricing'] as $pricingData) {
                    if (isset($pricingData['pricing_id']) && $pricingData['pricing_id']) {
                        // Update existing pricing
                        DishPricing::where('pricing_id', $pricingData['pricing_id'])
                            ->update([
                                'base_price' => $pricingData['base_price'],
                                'promotional_price' => $pricingData['promotional_price'],
                                'promo_start_date' => $pricingData['promo_start_date'],
                                'promo_end_date' => $pricingData['promo_end_date'],
                                'min_profit_margin' => $pricingData['min_profit_margin'] ?? 20,
                            ]);
                    } else {
                        // Create new pricing
                        DishPricing::create([
                            'dish_id' => $dish->dish_id,
                            'price_type' => $pricingData['price_type'],
                            'base_price' => $pricingData['base_price'],
                            'promotional_price' => $pricingData['promotional_price'],
                            'promo_start_date' => $pricingData['promo_start_date'],
                            'promo_end_date' => $pricingData['promo_end_date'],
                            'min_profit_margin' => $pricingData['min_profit_margin'] ?? 20,
                        ]);
                    }
                }
            }

            // Recalculate cost if ingredients were updated
            if (isset($validated['ingredients'])) {
                $this->calculateDishCost($dish);
            }
        });

        return redirect()->route('menu.show', $dish)
            ->with('success', 'Dish updated successfully!');
    }

    /**
     * Remove the specified dish from storage.
     */
    public function destroy(Dish $dish)
    {
        $dish->delete();

        return redirect()->route('menu.index')
            ->with('success', 'Dish deleted successfully!');
    }

    /**
     * Calculate and store dish cost
     */
    private function calculateDishCost(Dish $dish)
    {
        $totalCost = $dish->calculateCurrentCost();
        
        DishCost::create([
            'dish_id' => $dish->dish_id,
            'ingredient_cost' => $totalCost,
            'labor_cost' => 0,
            'overhead_cost' => 0,
            'total_cost' => $totalCost,
            'calculated_at' => now(),
        ]);
    }

    /**
     * Update dish status (activate/deactivate)
     */
    public function updateStatus(Request $request, Dish $dish)
    {
        $request->validate([
            'status' => 'required|in:draft,active,inactive,archived'
        ]);

        $dish->update(['status' => $request->status]);

        return redirect()->back()
            ->with('success', 'Dish status updated successfully!');
    }

    /**
     * Show menu analytics dashboard
     */
    public function analytics(Request $request)
    {
        $restaurantId = auth()->user()->restaurantData->id;
        
        $request->validate([
            'category_id' => 'nullable|exists:menu_categories,category_id',
            'period' => 'nullable|in:7,30,90,365',
            'status' => 'nullable|in:active,draft,inactive,archived'
        ]);

        $period = $request->input('period', 30);
        $categoryId = $request->input('category_id');
        $status = $request->input('status');

        // Get dish analytics with cost calculations
        $dishesQuery = Dish::with(['category', 'pricing', 'ingredients'])
            ->forRestaurant($restaurantId)
            ->when($categoryId, function($query, $categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->when($status, function($query, $status) {
                return $query->where('status', $status);
            });

        $dishes = $dishesQuery->get();

        $dishAnalytics = $dishes->map(function ($dish) {
            $totalCost = $dish->calculateCurrentCost();
            $avgPrice = $dish->pricing->avg('base_price') ?: 0;
            $profitMargin = $avgPrice > 0 ? (($avgPrice - $totalCost) / $avgPrice) * 100 : 0;

            return [
                'dish_id' => $dish->dish_id,
                'dish_name' => $dish->dish_name,
                'category_name' => $dish->category->category_name ?? 'Uncategorized',
                'total_cost' => $totalCost,
                'avg_price' => $avgPrice,
                'profit_margin' => $profitMargin,
                'status' => $dish->status,
                'popularity_score' => rand(1, 100), // Placeholder - would be based on actual orders
                'cost_trend' => collect(['up', 'down', 'stable'])->random(), // Placeholder
                'orders_count' => rand(0, 50), // Placeholder
                'revenue' => $avgPrice * rand(0, 50), // Placeholder
            ];
        });

        // Get category analytics
        $categories = MenuCategory::forRestaurant($restaurantId)
            ->withCount('dishes')
            ->get()
            ->map(function ($category) use ($dishes) {
                $categoryDishes = $dishes->where('category_id', $category->category_id);
                $totalCost = $categoryDishes->sum(function($dish) {
                    return $dish->calculateCurrentCost();
                });
                $totalRevenue = $categoryDishes->sum(function($dish) {
                    return $dish->pricing->avg('base_price') ?: 0;
                });
                $avgMargin = $totalRevenue > 0 ? (($totalRevenue - $totalCost) / $totalRevenue) * 100 : 0;

                return [
                    'category_id' => $category->category_id,
                    'category_name' => $category->category_name,
                    'dish_count' => $category->dishes_count,
                    'total_cost' => $totalCost,
                    'total_revenue' => $totalRevenue,
                    'avg_margin' => $avgMargin,
                ];
            });

        // Get cost analytics for ingredients
        $costAnalytics = collect();
        if ($dishes->isNotEmpty()) {
            $ingredientUsage = [];
            
            foreach ($dishes as $dish) {
                foreach ($dish->ingredients as $ingredient) {
                    $name = $ingredient->ingredient_name;
                    if (!isset($ingredientUsage[$name])) {
                        $ingredientUsage[$name] = [
                            'ingredient_name' => $name,
                            'total_usage' => 0,
                            'total_cost' => 0,
                            'dishes_count' => 0,
                        ];
                    }
                    
                    $ingredientUsage[$name]['total_usage'] += $ingredient->pivot->quantity;
                    $ingredientUsage[$name]['total_cost'] += $ingredient->cost_per_unit * $ingredient->pivot->quantity;
                    $ingredientUsage[$name]['dishes_count']++;
                }
            }

            $costAnalytics = collect($ingredientUsage)->map(function ($usage) {
                return [
                    'ingredient_name' => $usage['ingredient_name'],
                    'total_usage' => $usage['total_usage'],
                    'total_cost' => $usage['total_cost'],
                    'dishes_count' => $usage['dishes_count'],
                    'avg_cost_per_dish' => $usage['dishes_count'] > 0 ? $usage['total_cost'] / $usage['dishes_count'] : 0,
                    'cost_trend' => collect(['up', 'down', 'stable'])->random(), // Placeholder
                ];
            });
        }

        // Calculate summary metrics
        $totalDishes = $dishes->count();
        $activeDishes = $dishes->where('status', 'active')->count();
        $avgProfitMargin = $dishAnalytics->avg('profit_margin') ?: 0;
        $totalMenuCost = $dishAnalytics->sum('total_cost') ?: 0;

        return Inertia::render('Menu/Analytics', [
            'categories' => $categories->values(),
            'dishAnalytics' => $dishAnalytics->values(),
            'costAnalytics' => $costAnalytics->sortByDesc('total_cost')->take(20)->values(),
            'totalDishes' => $totalDishes,
            'activeDishes' => $activeDishes,
            'avgProfitMargin' => $avgProfitMargin,
            'totalMenuCost' => $totalMenuCost,
            'filters' => [
                'category_id' => $categoryId,
                'period' => $period,
                'status' => $status,
            ]
        ]);
    }
}
