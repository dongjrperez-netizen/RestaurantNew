<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Dish;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerOrderController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of customer orders
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $restaurantId = $user->restaurantData->id;

        $orders = CustomerOrder::with(['orderItems.dish', 'servedBy'])
            ->forRestaurant($restaurantId)
            ->when($request->status, function($query, $status) {
                return $query->byStatus($status);
            })
            ->when($request->order_type, function($query, $orderType) {
                return $query->where('order_type', $orderType);
            })
            ->when($request->date_from && $request->date_to, function($query) use ($request) {
                return $query->dateRange($request->date_from, $request->date_to);
            })
            ->when(!$request->date_from && !$request->date_to, function($query) {
                return $query->today();
            })
            ->orderByDesc('order_time')
            ->paginate(20);

        $statusCounts = [
            'pending' => CustomerOrder::forRestaurant($restaurantId)->today()->byStatus('pending')->count(),
            'confirmed' => CustomerOrder::forRestaurant($restaurantId)->today()->byStatus('confirmed')->count(),
            'preparing' => CustomerOrder::forRestaurant($restaurantId)->today()->byStatus('preparing')->count(),
            'ready' => CustomerOrder::forRestaurant($restaurantId)->today()->byStatus('ready')->count(),
            'completed' => CustomerOrder::forRestaurant($restaurantId)->today()->byStatus('completed')->count(),
        ];

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'filters' => [
                'status' => $request->status,
                'order_type' => $request->order_type,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ]
        ]);
    }

    /**
     * Show the form for creating a new order
     */
    public function create()
    {
        $restaurantId = auth()->user()->restaurantData->id;

        $dishes = Dish::with(['category', 'pricing'])
            ->forRestaurant($restaurantId)
            ->where('status', 'active')
            ->orderBy('dish_name')
            ->get();

        return Inertia::render('Orders/Create', [
            'dishes' => $dishes,
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:dine_in,takeout,delivery',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'required_if:order_type,delivery|string|nullable',
            'table_number' => 'required_if:order_type,dine_in|string|max:10|nullable',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,dish_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_instructions' => 'nullable|string',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $restaurantId = auth()->user()->restaurantData->id;

            // Check inventory availability for all items first
            foreach ($validated['items'] as $itemData) {
                $availability = $this->inventoryService->checkStockAvailability(
                    $itemData['dish_id'],
                    $itemData['quantity']
                );

                if (!$availability['can_fulfill']) {
                    $insufficientItems = collect($availability['ingredients'])
                        ->where('is_available', false)
                        ->pluck('ingredient_name')
                        ->implode(', ');

                    return redirect()->back()->withErrors([
                        'inventory' => "Insufficient stock for {$availability['dish_name']}. Missing: {$insufficientItems}"
                    ])->withInput();
                }
            }

            // Create the order
            $order = CustomerOrder::create([
                'restaurant_id' => $restaurantId,
                'order_number' => CustomerOrder::generateOrderNumber($restaurantId),
                'order_type' => $validated['order_type'],
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'table_number' => $validated['table_number'],
                'notes' => $validated['notes'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'delivery_fee' => $validated['delivery_fee'] ?? 0,
                'order_time' => now(),
                'subtotal' => 0, // Will be calculated
                'total_amount' => 0, // Will be calculated
            ]);

            // Create order items
            $subtotal = 0;
            foreach ($validated['items'] as $itemData) {
                $dish = Dish::findOrFail($itemData['dish_id']);

                $orderItem = CustomerOrderItem::createFromDish(
                    $dish,
                    $itemData['quantity'],
                    $validated['order_type'],
                    $itemData['special_instructions'] ?? null
                );

                $orderItem->order_id = $order->order_id;
                $orderItem->save();

                $subtotal += $orderItem->total_price;
            }

            // Update order totals
            $order->subtotal = $subtotal;
            $order->total_amount = $subtotal + $order->tax_amount + $order->delivery_fee - $order->discount_amount;
            $order->save();

            return redirect()->route('orders.show', $order->order_id)
                ->with('success', 'Order created successfully! Order number: ' . $order->order_number);
        });
    }

    /**
     * Display the specified order
     */
    public function show(CustomerOrder $order)
    {
        $order->load(['orderItems.dish', 'restaurant', 'servedBy']);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, CustomerOrder $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled'
        ]);

        try {
            $order->updateStatus($validated['status']);

            $message = "Order status updated to {$validated['status']}";

            // Add specific messages for inventory-affecting statuses
            if ($validated['status'] === 'confirmed') {
                $message .= '. Inventory has been automatically deducted.';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'status' => 'Failed to update order status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update order item status
     */
    public function updateItemStatus(Request $request, CustomerOrderItem $orderItem)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready,served'
        ]);

        $orderItem->updateStatus($validated['status']);

        return redirect()->back()->with('success', 'Order item status updated successfully!');
    }

    /**
     * Cancel an order
     */
    public function cancel(CustomerOrder $order)
    {
        if (!$order->canBeCancelled()) {
            return redirect()->back()->withErrors([
                'cancel' => 'This order cannot be cancelled in its current status.'
            ]);
        }

        $order->updateStatus('cancelled');

        return redirect()->back()->with('success', 'Order cancelled successfully!');
    }

    /**
     * Get kitchen display data (orders in preparing status)
     */
    public function kitchen()
    {
        $restaurantId = auth()->user()->restaurantData->id;

        $orders = CustomerOrder::with(['orderItems.dish'])
            ->forRestaurant($restaurantId)
            ->whereIn('status', ['confirmed', 'preparing'])
            ->orderBy('order_time')
            ->get();

        return Inertia::render('Orders/Kitchen', [
            'orders' => $orders,
        ]);
    }

    /**
     * Check inventory availability for a dish
     */
    public function checkInventory(Request $request)
    {
        $validated = $request->validate([
            'dish_id' => 'required|exists:dishes,dish_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $availability = $this->inventoryService->checkStockAvailability(
                $validated['dish_id'],
                $validated['quantity']
            );

            return response()->json([
                'success' => true,
                'data' => $availability,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get order analytics/reports
     */
    public function analytics(Request $request)
    {
        $restaurantId = auth()->user()->restaurantData->id;

        $dateFrom = $request->input('date_from', now()->startOfMonth());
        $dateTo = $request->input('date_to', now()->endOfMonth());

        $orders = CustomerOrder::forRestaurant($restaurantId)
            ->dateRange($dateFrom, $dateTo)
            ->with(['orderItems.dish'])
            ->get();

        $analytics = [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_order_value' => $orders->count() > 0 ? $orders->avg('total_amount') : 0,
            'orders_by_type' => $orders->groupBy('order_type')->map->count(),
            'orders_by_status' => $orders->groupBy('status')->map->count(),
            'top_dishes' => $orders->flatMap->orderItems
                ->groupBy('dish_name')
                ->map(function ($items) {
                    return [
                        'dish_name' => $items->first()->dish_name,
                        'quantity_sold' => $items->sum('quantity'),
                        'revenue' => $items->sum('total_price'),
                    ];
                })
                ->sortByDesc('quantity_sold')
                ->take(10)
                ->values(),
        ];

        return Inertia::render('Orders/Analytics', [
            'analytics' => $analytics,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]
        ]);
    }
}