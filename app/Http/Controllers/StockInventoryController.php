<?php

namespace App\Http\Controllers;

use App\Models\StockInventory;
use App\Models\Ingredients;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StockInventoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $query = StockInventory::with(['ingredient', 'restaurant'])
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

        $stocks = $query->orderBy('received_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $ingredients = Ingredients::forRestaurant($restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name']);

        // Get summary data
        $summary = [
            'total_stocks' => StockInventory::forRestaurant($restaurantId)->count(),
            'available_stocks' => StockInventory::forRestaurant($restaurantId)->available()->count(),
            'expiring_soon' => StockInventory::forRestaurant($restaurantId)->expiringWithin(7)->count(),
            'total_value' => StockInventory::forRestaurant($restaurantId)->available()->sum('total_cost'),
        ];

        return Inertia::render('StockInventory/Index', [
            'stocks' => $stocks,
            'ingredients' => $ingredients,
            'summary' => $summary,
            'filters' => $request->all(),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $ingredients = Ingredients::forRestaurant($restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'base_unit']);

        return Inertia::render('StockInventory/Create', [
            'ingredients' => $ingredients,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'batch_number' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'received_date' => 'required|date|before_or_equal:today',
            'supplier_name' => 'nullable|string|max:255',
            'purchase_order_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['restaurant_id'] = $user->restaurant_id;
        $validated['total_cost'] = $validated['quantity'] * $validated['cost_per_unit'];

        StockInventory::create($validated);

        return redirect()->route('stock-inventory.index')
            ->with('success', 'Stock inventory record created successfully.');
    }

    public function show(StockInventory $stockInventory)
    {
        $user = Auth::user();

        if ($stockInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $stockInventory->load([
            'ingredient',
            'kitchenInventories.transferredBy',
            'kitchenInventories' => function ($query) {
                $query->orderBy('transfer_date', 'desc');
            }
        ]);

        return Inertia::render('StockInventory/Show', [
            'stock' => $stockInventory,
        ]);
    }

    public function edit(StockInventory $stockInventory)
    {
        $user = Auth::user();

        if ($stockInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $ingredients = Ingredients::forRestaurant($user->restaurant_id)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'base_unit']);

        return Inertia::render('StockInventory/Edit', [
            'stock' => $stockInventory,
            'ingredients' => $ingredients,
        ]);
    }

    public function update(Request $request, StockInventory $stockInventory)
    {
        $user = Auth::user();

        if ($stockInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'batch_number' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'supplier_name' => 'nullable|string|max:255',
            'purchase_order_number' => 'nullable|string|max:255',
            'status' => 'required|in:available,transferred,expired,damaged',
            'notes' => 'nullable|string',
        ]);

        $validated['total_cost'] = $validated['quantity'] * $validated['cost_per_unit'];

        $stockInventory->update($validated);

        return redirect()->route('stock-inventory.show', $stockInventory)
            ->with('success', 'Stock inventory record updated successfully.');
    }

    public function destroy(StockInventory $stockInventory)
    {
        $user = Auth::user();

        if ($stockInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        // Check if stock has been transferred to kitchen
        if ($stockInventory->kitchenInventories()->count() > 0) {
            return back()->with('error', 'Cannot delete stock that has been transferred to kitchen.');
        }

        $stockInventory->delete();

        return redirect()->route('stock-inventory.index')
            ->with('success', 'Stock inventory record deleted successfully.');
    }

    public function transfer(Request $request, StockInventory $stockInventory)
    {
        $user = Auth::user();

        if ($stockInventory->restaurant_id !== $user->restaurant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01|max:' . $stockInventory->available_quantity,
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $kitchenInventory = $stockInventory->transferToKitchen(
                $validated['quantity'],
                $user->id,
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Stock transferred to kitchen successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function expiringSoon()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $expiringStocks = StockInventory::with(['ingredient'])
            ->forRestaurant($restaurantId)
            ->available()
            ->expiringWithin(7)
            ->orderBy('expiry_date', 'asc')
            ->get();

        return Inertia::render('StockInventory/ExpiringSoon', [
            'expiringStocks' => $expiringStocks,
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id;

        $stats = [
            'total_items' => StockInventory::forRestaurant($restaurantId)->count(),
            'available_items' => StockInventory::forRestaurant($restaurantId)->available()->count(),
            'total_value' => StockInventory::forRestaurant($restaurantId)->available()->sum('total_cost'),
            'expiring_soon' => StockInventory::forRestaurant($restaurantId)->expiringWithin(7)->count(),
            'expired' => StockInventory::forRestaurant($restaurantId)
                ->where('status', 'available')
                ->whereDate('expiry_date', '<', Carbon::now())
                ->count(),
        ];

        // Recent stocks
        $recentStocks = StockInventory::with(['ingredient'])
            ->forRestaurant($restaurantId)
            ->orderBy('received_date', 'desc')
            ->take(5)
            ->get();

        // Low quantity stocks
        $lowQuantityStocks = StockInventory::with(['ingredient'])
            ->forRestaurant($restaurantId)
            ->available()
            ->where('quantity', '<', 10) // Configurable threshold
            ->orderBy('quantity', 'asc')
            ->take(10)
            ->get();

        return Inertia::render('StockInventory/Dashboard', [
            'stats' => $stats,
            'recentStocks' => $recentStocks,
            'lowQuantityStocks' => $lowQuantityStocks,
        ]);
    }
}