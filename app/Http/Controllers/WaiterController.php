<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Employee;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Dish;
use App\Models\MenuPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WaiterController extends Controller
{
    public function dashboard()
    {
        // Get the authenticated employee
        $employee = Auth::guard('employee')->user();

        if (!$employee || $employee->role->role_name !== 'Waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get tables for the restaurant this waiter belongs to
        $tables = Table::where('user_id', $employee->user_id)
            ->orderBy('table_number')
            ->get();

        // Get active menu plan for the restaurant
        // Priority: is_active flag (manager's choice) over date range
        $activeMenuPlan = MenuPlan::where('restaurant_id', $employee->user_id)
            ->where('is_active', true)
            ->first();

        // Get available dishes from active menu plan or all active dishes
        if ($activeMenuPlan) {
            $dishesCollection = $activeMenuPlan->dishes()
                ->with(['category'])
                ->where('is_available', true)
                ->where('status', 'active')
                ->get()
                ->groupBy('category.category_name');
        } else {
            // Fallback to all active dishes if no active menu plan
            $dishesCollection = Dish::where('restaurant_id', $employee->user_id)
                ->with(['category'])
                ->where('is_available', true)
                ->where('status', 'active')
                ->get()
                ->groupBy('category.category_name');
        }

        // Convert collection to array for proper JSON serialization
        $dishes = [];
        foreach ($dishesCollection as $categoryName => $categoryDishes) {
            $dishes[$categoryName ?: 'Uncategorized'] = $categoryDishes->toArray();
        }


        return Inertia::render('Waiter/Dashboard', [
            'tables' => $tables,
            'employee' => $employee->load('role'),
            'activeMenuPlan' => $activeMenuPlan,
            'dishes' => $dishes,
        ]);
    }

    public function updateTableStatus(Request $request, Table $table)
    {
        $employee = Auth::guard('employee')->user();

        // Ensure the table belongs to the same restaurant as the waiter
        if ($table->user_id !== $employee->user_id) {
            abort(403, 'Unauthorized access to table.');
        }

        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance'
        ]);

        $table->update($validated);

        return redirect()->back()->with('success', 'Table status updated successfully.');
    }

    public function takeOrder()
    {
        $employee = Auth::guard('employee')->user();

        if (!$employee || $employee->role->role_name !== 'Waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get tables for the restaurant this waiter belongs to
        $tables = Table::where('user_id', $employee->user_id)
            ->orderBy('table_number')
            ->get();

        return Inertia::render('Waiter/TakeOrder', [
            'tables' => $tables,
            'employee' => $employee->load('role'),
        ]);
    }

    public function createOrder(Request $request, $tableId)
    {
        $employee = Auth::guard('employee')->user();

        if (!$employee || $employee->role->role_name !== 'Waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        // Get the table and ensure it belongs to the same restaurant
        $table = Table::where('id', $tableId)
            ->where('user_id', $employee->user_id)
            ->firstOrFail();

        // Check for existing active order for this table
        $existingOrder = null;
        if ($table->status === 'occupied') {
            $existingOrder = CustomerOrder::where('table_id', $tableId)
                ->whereIn('status', ['pending', 'in_progress', 'ready'])
                ->with(['orderItems.dish'])
                ->latest()
                ->first();
        }

        // Get active menu plan for the restaurant
        // Priority: is_active flag (manager's choice) over date range
        // Note: MenuPlan uses restaurant_id which corresponds to the user_id of the restaurant owner
        $activeMenuPlan = MenuPlan::where('restaurant_id', $employee->user_id)
            ->where('is_active', true)
            ->first();

        // Get available dishes from active menu plan or all active dishes
        if ($activeMenuPlan) {
            $dishes = $activeMenuPlan->dishes()
                ->with(['category'])
                ->where('is_available', true)
                ->where('status', 'active')
                ->get();
        } else {
            // Fallback to all active dishes if no active menu plan
            // Note: Dish uses restaurant_id which corresponds to the user_id of the restaurant owner
            $dishes = Dish::where('restaurant_id', $employee->user_id)
                ->with(['category'])
                ->where('is_available', true)
                ->where('status', 'active')
                ->get();
        }

        return Inertia::render('Waiter/CreateOrder', [
            'table' => $table,
            'dishes' => $dishes,
            'employee' => $employee->load('role'),
            'existingOrder' => $existingOrder,
        ]);
    }

    public function storeOrder(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        if (!$employee || $employee->role->role_name !== 'Waiter') {
            abort(403, 'Access denied. Waiters only.');
        }

        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'order_items' => 'required|array|min:1',
            'order_items.*.dish_id' => 'required|exists:dishes,dish_id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.special_instructions' => 'nullable|string',
        ]);

        // Verify table belongs to the same restaurant
        $table = Table::where('id', $validated['table_id'])
            ->where('user_id', $employee->user_id)
            ->firstOrFail();

        // Check for existing active order for occupied tables
        $existingOrder = null;
        if ($table->status === 'occupied') {
            $existingOrder = CustomerOrder::where('table_id', $validated['table_id'])
                ->whereIn('status', ['pending', 'in_progress', 'ready'])
                ->latest()
                ->first();
        }

        DB::transaction(function () use ($validated, $employee, $table, $existingOrder) {

            if ($existingOrder) {
                // Add items to existing order
                $order = $existingOrder;

                // Update customer name and notes if provided
                if (!empty($validated['customer_name']) && empty($order->customer_name)) {
                    $order->customer_name = $validated['customer_name'];
                }
                if (!empty($validated['notes'])) {
                    $order->notes = trim($order->notes . ' ' . $validated['notes']);
                }
                $order->save();
            } else {
                // Create new order
                $order = CustomerOrder::create([
                    'table_id' => $validated['table_id'],
                    'employee_id' => $employee->employee_id,
                    'user_id' => $employee->user_id,
                    'customer_name' => $validated['customer_name'],
                    'notes' => $validated['notes'],
                    'status' => 'pending',
                    'ordered_at' => now(),
                ]);
            }

            // Add order items
            foreach ($validated['order_items'] as $item) {
                $dish = Dish::find($item['dish_id']);

                // Check if this dish already exists in the order
                $existingItem = CustomerOrderItem::where('order_id', $order->order_id)
                    ->where('dish_id', $item['dish_id'])
                    ->where('special_instructions', $item['special_instructions'] ?? null)
                    ->first();

                if ($existingItem) {
                    // Update quantity of existing item
                    $existingItem->quantity += $item['quantity'];
                    $existingItem->save();
                } else {
                    // Create new order item
                    CustomerOrderItem::create([
                        'order_id' => $order->order_id,
                        'dish_id' => $item['dish_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $dish->price,
                        'special_instructions' => $item['special_instructions'] ?? null,
                        'status' => 'pending',
                    ]);
                }
            }

            // Update table status to occupied if it was available
            if ($table->status === 'available') {
                $table->update(['status' => 'occupied']);
            }

            // Recalculate order totals
            $order->calculateTotals();
        });

        return redirect()
            ->route('waiter.dashboard')
            ->with('success', $existingOrder ? 'Items added to existing order successfully!' : 'Order created successfully!');
    }
}