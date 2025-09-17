<?php

namespace App\Http\Controllers;

use App\Models\KitchenInventory;
use App\Models\Ingredients;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KitchenInventoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $query = KitchenInventory::with(['ingredient', 'stockInventory', 'transferredBy'])
            ->forRestaurant($restaurantId);

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('ingredient_id') && $request->ingredient_id) {
            $query->forIngredient($request->ingredient_id);
        }

        if ($request->has('expiring_soon') && $request->expiring_soon === '1') {
            $query->expiringWithin(7);
        }

        if ($request->has('search') && $request->search) {
            $query->whereHas('ingredient', function ($q) use ($request) {
                $q->where('ingredient_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        $kitchenStocks = $query->orderBy('transfer_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $ingredients = Ingredients::forRestaurant($restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name']);

        // Get summary data
        $summary = [
            'total_transfers' => KitchenInventory::forRestaurant($restaurantId)->count(),
            'active_stocks' => KitchenInventory::forRestaurant($restaurantId)->active()->count(),
            'expiring_soon' => KitchenInventory::forRestaurant($restaurantId)->expiringWithin(7)->count(),
            'total_remaining' => KitchenInventory::forRestaurant($restaurantId)->active()->sum('quantity_remaining'),
        ];

        return Inertia::render('KitchenInventory/Index', [
            'kitchenStocks' => $kitchenStocks,
            'ingredients' => $ingredients,
            'summary' => $summary,
            'filters' => $request->all(),
        ]);
    }

    public function show(KitchenInventory $kitchenInventory)
    {
        $user = Auth::user();

        if ($kitchenInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $kitchenInventory->load([
            'ingredient',
            'stockInventory',
            'transferredBy',
        ]);

        return Inertia::render('KitchenInventory/Show', [
            'kitchenStock' => $kitchenInventory,
        ]);
    }

    public function useStock(Request $request, KitchenInventory $kitchenInventory)
    {
        $user = Auth::user();

        if ($kitchenInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity_used' => 'required|numeric|min:0.01|max:' . $kitchenInventory->quantity_remaining,
            'usage_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $kitchenInventory->useQuantity(
                $validated['quantity_used'],
                $validated['usage_notes'] ?? null
            );

            return back()->with('success', 'Stock usage recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function returnStock(Request $request, KitchenInventory $kitchenInventory)
    {
        $user = Auth::user();

        if ($kitchenInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $maxReturnQuantity = $kitchenInventory->getUsedQuantity();

        $validated = $request->validate([
            'quantity_returned' => 'required|numeric|min:0.01|max:' . $maxReturnQuantity,
            'return_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $kitchenInventory->returnToStock(
                $validated['quantity_returned'],
                $validated['return_notes'] ?? null
            );

            return back()->with('success', 'Stock returned successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markExpired(KitchenInventory $kitchenInventory)
    {
        $user = Auth::user();

        if ($kitchenInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $kitchenInventory->markAsExpired();

        return back()->with('success', 'Kitchen stock marked as expired.');
    }

    public function expiringSoon()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $expiringKitchenStocks = KitchenInventory::with(['ingredient', 'stockInventory'])
            ->forRestaurant($restaurantId)
            ->active()
            ->expiringWithin(7)
            ->orderBy('expiry_date', 'asc')
            ->get();

        return Inertia::render('KitchenInventory/ExpiringSoon', [
            'expiringKitchenStocks' => $expiringKitchenStocks,
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $stats = [
            'total_transfers' => KitchenInventory::forRestaurant($restaurantId)->count(),
            'active_stocks' => KitchenInventory::forRestaurant($restaurantId)->active()->count(),
            'total_remaining' => KitchenInventory::forRestaurant($restaurantId)->active()->sum('quantity_remaining'),
            'expiring_soon' => KitchenInventory::forRestaurant($restaurantId)->expiringWithin(7)->count(),
            'expired' => KitchenInventory::forRestaurant($restaurantId)->expired()->count(),
            'used_up' => KitchenInventory::forRestaurant($restaurantId)->where('status', 'used_up')->count(),
        ];

        // Recent transfers
        $recentTransfers = KitchenInventory::with(['ingredient', 'transferredBy'])
            ->forRestaurant($restaurantId)
            ->orderBy('transfer_date', 'desc')
            ->take(10)
            ->get();

        // Most used ingredients (by quantity)
        $mostUsedIngredients = KitchenInventory::with('ingredient')
            ->forRestaurant($restaurantId)
            ->selectRaw('ingredient_id, SUM(quantity_transferred - quantity_remaining) as total_used')
            ->where('transfer_date', '>=', Carbon::now()->subDays(30)) // Last 30 days
            ->groupBy('ingredient_id')
            ->orderBy('total_used', 'desc')
            ->take(10)
            ->get();

        // Low kitchen stock (ingredients with less than threshold remaining)
        $lowKitchenStock = KitchenInventory::with('ingredient')
            ->forRestaurant($restaurantId)
            ->active()
            ->where('quantity_remaining', '<', 5) // Configurable threshold
            ->orderBy('quantity_remaining', 'asc')
            ->take(10)
            ->get();

        return Inertia::render('KitchenInventory/Dashboard', [
            'stats' => $stats,
            'recentTransfers' => $recentTransfers,
            'mostUsedIngredients' => $mostUsedIngredients,
            'lowKitchenStock' => $lowKitchenStock,
        ]);
    }

    public function batchMarkExpired(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $validated = $request->validate([
            'kitchen_stock_ids' => 'required|array',
            'kitchen_stock_ids.*' => 'exists:kitchen_inventories,kitchen_stock_id',
        ]);

        $updatedCount = KitchenInventory::whereIn('kitchen_stock_id', $validated['kitchen_stock_ids'])
            ->forRestaurant($restaurantId)
            ->active()
            ->expired()
            ->update(['status' => 'expired']);

        return back()->with('success', "Marked {$updatedCount} expired kitchen stocks.");
    }

    public function usageReport(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        // Usage by ingredient
        $usageByIngredient = KitchenInventory::with('ingredient')
            ->forRestaurant($restaurantId)
            ->whereDate('transfer_date', '>=', $startDate)
            ->whereDate('transfer_date', '<=', $endDate)
            ->selectRaw('ingredient_id, SUM(quantity_transferred - quantity_remaining) as total_used')
            ->groupBy('ingredient_id')
            ->orderBy('total_used', 'desc')
            ->get();

        // Daily usage
        $dailyUsage = KitchenInventory::forRestaurant($restaurantId)
            ->whereDate('transfer_date', '>=', $startDate)
            ->whereDate('transfer_date', '<=', $endDate)
            ->selectRaw('DATE(transfer_date) as date, SUM(quantity_transferred - quantity_remaining) as total_used')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Waste (expired items)
        $waste = KitchenInventory::with('ingredient')
            ->forRestaurant($restaurantId)
            ->where('status', 'expired')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get();

        return Inertia::render('KitchenInventory/UsageReport', [
            'usageByIngredient' => $usageByIngredient,
            'dailyUsage' => $dailyUsage,
            'waste' => $waste,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}