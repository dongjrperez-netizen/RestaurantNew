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

        return Inertia::render('Waiter/Dashboard', [
            'tables' => $tables,
            'employee' => $employee->load('role'),
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

        // Get active menu plan for the restaurant
        // Note: MenuPlan uses restaurant_id which corresponds to the user_id of the restaurant owner
        $activeMenuPlan = MenuPlan::where('restaurant_id', $employee->user_id)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
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

        DB::transaction(function () use ($validated, $employee, $table) {
            // Create the order
            $order = CustomerOrder::create([
                'table_id' => $validated['table_id'],
                'employee_id' => $employee->employee_id,
                'user_id' => $employee->user_id,
                'customer_name' => $validated['customer_name'],
                'notes' => $validated['notes'],
                'status' => 'pending',
                'ordered_at' => now(),
            ]);

            // Create order items
            foreach ($validated['order_items'] as $item) {
                $dish = Dish::find($item['dish_id']);

                CustomerOrderItem::create([
                    'order_id' => $order->order_id,
                    'dish_id' => $item['dish_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $dish->price,
                    'special_instructions' => $item['special_instructions'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // Update table status to occupied if it was available
            if ($table->status === 'available') {
                $table->update(['status' => 'occupied']);
            }
        });

        return redirect()
            ->route('waiter.dashboard')
            ->with('success', 'Order created successfully!');
    }
}