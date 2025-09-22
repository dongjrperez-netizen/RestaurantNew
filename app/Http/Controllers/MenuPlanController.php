<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\MenuPlan;
use App\Models\MenuPlanDish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MenuPlanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $menuPlans = MenuPlan::with(['menuPlanDishes.dish'])
            ->withCount('menuPlanDishes as dishes_count')
            ->forRestaurant($restaurantId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('MenuPlanning/Index', [
            'menuPlans' => $menuPlans,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dishes = Dish::where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->with('ingredients')
            ->orderBy('dish_name')
            ->get();

        $ingredients = \App\Models\Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get();

        return Inertia::render('MenuPlanning/Create', [
            'dishes' => $dishes,
            'ingredients' => $ingredients,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $request->validate([
            'plan_name' => 'required|string|max:255',
            'plan_type' => ['required', Rule::in(['daily', 'weekly'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'dishes' => 'array',
            'dishes.*.dish_id' => 'required|exists:dishes,dish_id',
            'dishes.*.planned_quantity' => 'required|integer|min:1',
            'dishes.*.meal_type' => ['nullable', Rule::in(['breakfast', 'lunch', 'dinner', 'snack'])],
            'dishes.*.planned_date' => 'required|date',
            'dishes.*.day_of_week' => 'nullable|integer|min:0|max:6',
            'dishes.*.notes' => 'nullable|string',
        ]);

        $menuPlan = MenuPlan::create([
            'restaurant_id' => $restaurantId,
            'plan_name' => $request->plan_name,
            'plan_type' => $request->plan_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'is_active' => true,
        ]);

        if ($request->has('dishes') && is_array($request->dishes)) {
            foreach ($request->dishes as $dishData) {
                MenuPlanDish::create([
                    'menu_plan_id' => $menuPlan->menu_plan_id,
                    'dish_id' => $dishData['dish_id'],
                    'planned_quantity' => $dishData['planned_quantity'],
                    'meal_type' => $dishData['meal_type'] ?? null,
                    'planned_date' => $dishData['planned_date'],
                    'day_of_week' => $dishData['day_of_week'] ?? null,
                    'notes' => $dishData['notes'] ?? null,
                ]);
            }
        }

        return redirect()->route('menu-planning.show', $menuPlan->menu_plan_id)
            ->with('success', 'Menu plan created successfully.');
    }

    public function show($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $menuPlan = MenuPlan::with(['dishes', 'menuPlanDishes.dish'])
            ->forRestaurant($restaurantId)
            ->findOrFail($id);

        return Inertia::render('MenuPlanning/Show', [
            'menuPlan' => $menuPlan,
        ]);
    }

    public function edit($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $menuPlan = MenuPlan::with(['menuPlanDishes.dish'])
            ->forRestaurant($restaurantId)
            ->findOrFail($id);

        $dishes = Dish::where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->orderBy('dish_name')
            ->get();

        return Inertia::render('MenuPlanning/Edit', [
            'menuPlan' => $menuPlan,
            'dishes' => $dishes,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            \Log::info('MenuPlan update started', ['id' => $id, 'data' => $request->all()]);

            $user = Auth::user();
            $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

            $menuPlan = MenuPlan::forRestaurant($restaurantId)->findOrFail($id);

            $request->validate([
                'plan_name' => 'required|string|max:255',
                'plan_type' => ['required', Rule::in(['daily', 'weekly'])],
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'dishes' => 'array',
                'dishes.*.dish_id' => 'required|exists:dishes,dish_id',
                'dishes.*.planned_quantity' => 'required|integer|min:1',
                'dishes.*.meal_type' => ['nullable', Rule::in(['breakfast', 'lunch', 'dinner', 'snack'])],
                'dishes.*.planned_date' => 'required|date',
                'dishes.*.day_of_week' => 'nullable|integer|min:0|max:6',
                'dishes.*.notes' => 'nullable|string',
            ]);

            \Log::info('MenuPlan validation passed');

        $menuPlan->update([
            'plan_name' => $request->plan_name,
            'plan_type' => $request->plan_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        // Remove existing dishes and add new ones
        $menuPlan->menuPlanDishes()->delete();

        if ($request->has('dishes') && is_array($request->dishes)) {
            foreach ($request->dishes as $dishData) {
                MenuPlanDish::create([
                    'menu_plan_id' => $menuPlan->menu_plan_id,
                    'dish_id' => $dishData['dish_id'],
                    'planned_quantity' => $dishData['planned_quantity'],
                    'meal_type' => $dishData['meal_type'] ?? null,
                    'planned_date' => $dishData['planned_date'],
                    'day_of_week' => $dishData['day_of_week'] ?? null,
                    'notes' => $dishData['notes'] ?? null,
                ]);
            }
        }

            \Log::info('MenuPlan update completed successfully');

            return redirect()->route('menu-planning.show', $menuPlan->menu_plan_id)
                ->with('success', 'Menu plan updated successfully.');

        } catch (\Exception $e) {
            \Log::error('MenuPlan update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update menu plan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $menuPlan = MenuPlan::forRestaurant($restaurantId)->findOrFail($id);
        $menuPlan->delete();

        return redirect()->route('menu-planning.index')
            ->with('success', 'Menu plan deleted successfully.');
    }

    public function getActiveMenuPlan(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $date = $request->get('date', now()->format('Y-m-d'));
        $mealType = $request->get('meal_type');

        $activeMenuPlan = MenuPlan::with(['menuPlanDishes.dish'])
            ->forRestaurant($restaurantId)
            ->active()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if (! $activeMenuPlan) {
            return response()->json(['message' => 'No active menu plan found for this date'], 404);
        }

        $dishes = $activeMenuPlan->menuPlanDishes()
            ->with('dish')
            ->where('planned_date', $date)
            ->when($mealType, function ($query) use ($mealType) {
                return $query->where('meal_type', $mealType);
            })
            ->get();

        return response()->json([
            'menu_plan' => $activeMenuPlan,
            'dishes' => $dishes,
        ]);
    }

    public function toggleActive($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $menuPlan = MenuPlan::forRestaurant($restaurantId)->findOrFail($id);
        $menuPlan->update(['is_active' => ! $menuPlan->is_active]);

        $status = $menuPlan->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Menu plan {$status} successfully.");
    }

    public function mobileView($id, $date)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $menuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
            ->forRestaurant($restaurantId)
            ->findOrFail($id);

        // Get dishes for the specific date
        $dishesForDate = $menuPlan->menuPlanDishes()
            ->with(['dish.category'])
            ->whereDate('planned_date', $date)
            ->get();

        // Group dishes by category
        $dishesByCategory = $dishesForDate->groupBy(function ($menuPlanDish) {
            return $menuPlanDish->dish->category->category_name ?? 'Uncategorized';
        })->map(function ($dishes) {
            return $dishes->map(function ($menuPlanDish) {
                return [
                    'dish_id' => $menuPlanDish->dish->dish_id,
                    'dish_name' => $menuPlanDish->dish->dish_name,
                    'description' => $menuPlanDish->dish->description,
                    'price' => $menuPlanDish->dish->price,
                    'image_url' => $menuPlanDish->dish->image_url,
                    'allergens' => $menuPlanDish->dish->allergens,
                    'dietary_tags' => $menuPlanDish->dish->dietary_tags,
                    'calories' => $menuPlanDish->dish->calories,
                    'preparation_time' => $menuPlanDish->dish->preparation_time,
                    'planned_quantity' => $menuPlanDish->planned_quantity,
                    'meal_type' => $menuPlanDish->meal_type,
                    'notes' => $menuPlanDish->notes,
                ];
            });
        });

        // For testing employee login redirect - temporarily use test view
        if (request()->query('test') === 'redirect') {
            return Inertia::render('MenuPlanning/MobileViewTest', [
                'menuPlan' => $menuPlan,
                'date' => $date,
                'dishesByCategory' => $dishesByCategory,
                'totalDishes' => $dishesForDate->count(),
            ]);
        }

        return Inertia::render('MenuPlanning/MobileView', [
            'menuPlan' => $menuPlan,
            'selectedDate' => $date,
            'dishesByCategory' => $dishesByCategory,
            'totalDishes' => $dishesForDate->count(),
        ]);
    }
}
