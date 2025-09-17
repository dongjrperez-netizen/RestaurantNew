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
        $restaurantId = $user->restaurant_data->id ?? null;

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $menuPlans = MenuPlan::with(['dishes'])
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
        $restaurantId = $user->restaurant_data->id ?? null;

        if (! $restaurantId) {
            return redirect()->route('dashboard')->with('error', 'No restaurant data found.');
        }

        $dishes = Dish::where('restaurant_id', $restaurantId)
            ->where('is_available', true)
            ->orderBy('dish_name')
            ->get();

        return Inertia::render('MenuPlanning/Create', [
            'dishes' => $dishes,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

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
            'dishes.*.day_of_week' => 'nullable|integer|min:1|max:7',
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

        return redirect()->route('menu-plans.show', $menuPlan->menu_plan_id)
            ->with('success', 'Menu plan created successfully.');
    }

    public function show($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

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
        $restaurantId = $user->restaurant_data->id ?? null;

        $menuPlan = MenuPlan::with(['menuPlanDishes.dish'])
            ->forRestaurant($restaurantId)
            ->findOrFail($id);

        $dishes = Dish::where('restaurant_id', $restaurantId)
            ->where('is_available', true)
            ->orderBy('dish_name')
            ->get();

        return Inertia::render('MenuPlanning/Edit', [
            'menuPlan' => $menuPlan,
            'dishes' => $dishes,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

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
            'dishes.*.day_of_week' => 'nullable|integer|min:1|max:7',
            'dishes.*.notes' => 'nullable|string',
        ]);

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

        return redirect()->route('menu-plans.show', $menuPlan->menu_plan_id)
            ->with('success', 'Menu plan updated successfully.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

        $menuPlan = MenuPlan::forRestaurant($restaurantId)->findOrFail($id);
        $menuPlan->delete();

        return redirect()->route('menu-plans.index')
            ->with('success', 'Menu plan deleted successfully.');
    }

    public function getActiveMenuPlan(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_data->id ?? null;

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
        $restaurantId = $user->restaurant_data->id ?? null;

        $menuPlan = MenuPlan::forRestaurant($restaurantId)->findOrFail($id);
        $menuPlan->update(['is_active' => ! $menuPlan->is_active]);

        $status = $menuPlan->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Menu plan {$status} successfully.");
    }
}
