<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Ingredients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }
        
        $restaurantId = $user->restaurantData->id;
        
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders
        ]);
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with([
            'supplier', 
            'items.ingredient', 
            'bill.payments',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }
        
        $restaurantId = $user->restaurantData->id;
        
        $suppliers = Supplier::with(['ingredients' => function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            }, 'ingredients.pivot'])
            ->where('is_active', true)
            ->whereHas('ingredients', function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->orderBy('supplier_name')
            ->get();

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->with(['suppliers.pivot'])
            ->orderBy('ingredient_name')
            ->get();

        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => $suppliers,
            'ingredients' => $ingredients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'expected_delivery_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'delivery_instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.ordered_quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.unit_of_measure' => 'required|string|max:50',
            'items.*.notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['ordered_quantity'] * $item['unit_price'];
            });

            $user = auth()->user();
            if (!$user->restaurantData) {
                return redirect()->back()->with('error', 'No restaurant data found.');
            }

            $purchaseOrder = PurchaseOrder::create([
                'restaurant_id' => $user->restaurantData->id,
                'supplier_id' => $validated['supplier_id'],
                'status' => 'draft',
                'order_date' => now(),
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'notes' => $validated['notes'],
                'delivery_instructions' => $validated['delivery_instructions'],
                'created_by_user_id' => auth()->id()
            ]);

            foreach ($validated['items'] as $item) {
                $totalPrice = $item['ordered_quantity'] * $item['unit_price'];
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->purchase_order_id,
                    'ingredient_id' => $item['ingredient_id'],
                    'ordered_quantity' => $item['ordered_quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                    'unit_of_measure' => $item['unit_of_measure'],
                    'notes' => $item['notes']
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', 'Purchase Order created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating purchase order: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with(['items.ingredient', 'supplier'])
            ->findOrFail($id);

        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return redirect()->route('purchase-orders.show', $id)
                ->with('error', 'Cannot edit purchase order with status: ' . $purchaseOrder->status);
        }

        $user = auth()->user();
        if (!$user->restaurantData) {
            return redirect()->back()->with('error', 'No restaurant data found. Please complete your restaurant setup.');
        }
        
        $restaurantId = $user->restaurantData->id;
        
        $suppliers = Supplier::with(['ingredients' => function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            }, 'ingredients.pivot'])
            ->where('is_active', true)
            ->whereHas('ingredients', function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->orderBy('supplier_name')
            ->get();

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->with(['suppliers.pivot'])
            ->orderBy('ingredient_name')
            ->get();

        return Inertia::render('PurchaseOrders/Edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => $suppliers,
            'ingredients' => $ingredients
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return redirect()->back()
                ->with('error', 'Cannot update purchase order with status: ' . $purchaseOrder->status);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'expected_delivery_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'delivery_instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.ordered_quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.unit_of_measure' => 'required|string|max:50',
            'items.*.notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['ordered_quantity'] * $item['unit_price'];
            });

            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'notes' => $validated['notes'],
                'delivery_instructions' => $validated['delivery_instructions']
            ]);

            $purchaseOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                $totalPrice = $item['ordered_quantity'] * $item['unit_price'];
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->purchase_order_id,
                    'ingredient_id' => $item['ingredient_id'],
                    'ordered_quantity' => $item['ordered_quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                    'unit_of_measure' => $item['unit_of_measure'],
                    'notes' => $item['notes']
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', 'Purchase Order updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating purchase order: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Can only approve purchase orders with pending status.');
        }

        $purchaseOrder->update([
            'status' => 'sent',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now()
        ]);

        return redirect()->back()
            ->with('success', 'Purchase Order approved and sent to supplier.');
    }

    public function submit($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Can only submit draft purchase orders.');
        }

        $purchaseOrder->update(['status' => 'pending']);

        return redirect()->back()
            ->with('success', 'Purchase Order submitted for approval.');
    }

    public function cancel($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (in_array($purchaseOrder->status, ['delivered', 'cancelled'])) {
            return redirect()->back()
                ->with('error', 'Cannot cancel purchase order with status: ' . $purchaseOrder->status);
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->back()
            ->with('success', 'Purchase Order cancelled successfully.');
    }

    public function receive($id)
    {
        $purchaseOrder = PurchaseOrder::with('items.ingredient')->findOrFail($id);

        if (!in_array($purchaseOrder->status, ['confirmed', 'partially_delivered'])) {
            return redirect()->back()
                ->with('error', 'Can only receive confirmed purchase orders.');
        }

        return Inertia::render('PurchaseOrders/Receive', [
            'purchaseOrder' => $purchaseOrder
        ]);
    }

    public function processReceive(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::with('items.ingredient')->findOrFail($id);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,purchase_order_item_id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'actual_delivery_date' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $allItemsFullyReceived = true;

            foreach ($validated['items'] as $itemData) {
                $item = PurchaseOrderItem::find($itemData['purchase_order_item_id']);
                $newReceivedQuantity = $item->received_quantity + $itemData['received_quantity'];
                
                $item->update([
                    'received_quantity' => $newReceivedQuantity
                ]);

                if ($newReceivedQuantity < $item->ordered_quantity) {
                    $allItemsFullyReceived = false;
                }

                if ($itemData['received_quantity'] > 0) {
                    $ingredient = $item->ingredient;
                    $ingredient->increment('current_stock', $itemData['received_quantity']);
                }
            }

            $newStatus = $allItemsFullyReceived ? 'delivered' : 'partially_delivered';
            
            $purchaseOrder->update([
                'status' => $newStatus,
                'actual_delivery_date' => $validated['actual_delivery_date']
            ]);

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->purchase_order_id)
                ->with('success', 'Delivery received and stock updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing delivery: ' . $e->getMessage());
        }
    }
}
