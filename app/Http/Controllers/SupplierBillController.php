<?php

namespace App\Http\Controllers;

use App\Models\SupplierBill;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class SupplierBillController extends Controller
{
    public function index()
    {
        $restaurantId = auth()->user()->restaurantData->id;
        
        $bills = SupplierBill::with(['supplier', 'purchaseOrder', 'payments'])
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($bill) {
                return [
                    ...$bill->toArray(),
                    'is_overdue' => $bill->is_overdue,
                    'days_overdue' => $bill->days_overdue
                ];
            });

        $summary = [
            'total_outstanding' => $bills->sum('outstanding_amount'),
            'overdue_amount' => $bills->where('is_overdue', true)->sum('outstanding_amount'),
            'total_bills' => $bills->count(),
            'overdue_count' => $bills->where('is_overdue', true)->count()
        ];

        return Inertia::render('Bills/Index', [
            'bills' => $bills,
            'summary' => $summary
        ]);
    }

    public function show($id)
    {
        $bill = SupplierBill::with([
            'supplier', 
            'purchaseOrder.items.ingredient', 
            'payments.createdBy'
        ])->findOrFail($id);

        $bill->is_overdue = $bill->is_overdue;
        $bill->days_overdue = $bill->days_overdue;

        return Inertia::render('Bills/Show', [
            'bill' => $bill
        ]);
    }

    public function create()
    {
        $restaurantId = auth()->user()->restaurantData->id;
        
        $suppliers = Supplier::where('is_active', true)
            ->orderBy('supplier_name')
            ->get();

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['delivered', 'partially_delivered'])
            ->whereDoesntHave('bill')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Bills/Create', [
            'suppliers' => $suppliers,
            'purchaseOrders' => $purchaseOrders
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,purchase_order_id',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $bill = SupplierBill::create([
            'purchase_order_id' => $validated['purchase_order_id'],
            'restaurant_id' => auth()->user()->restaurantData->id,
            'supplier_id' => $validated['supplier_id'],
            'supplier_invoice_number' => $validated['supplier_invoice_number'],
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'discount_amount' => $validated['discount_amount'],
            'total_amount' => $validated['total_amount'],
            'outstanding_amount' => $validated['total_amount'],
            'status' => 'pending',
            'notes' => $validated['notes']
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('supplier-bills', 'public');
            $bill->update(['attachment_path' => $path]);
        }

        return redirect()->route('bills.show', $bill->bill_id)
            ->with('success', 'Bill created successfully.');
    }

    public function createFromPurchaseOrder($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->findOrFail($purchaseOrderId);

        if ($purchaseOrder->bill) {
            return redirect()->route('bills.show', $purchaseOrder->bill->bill_id)
                ->with('info', 'Bill already exists for this purchase order.');
        }

        $supplier = $purchaseOrder->supplier;
        $dueDate = $this->calculateDueDate($purchaseOrder->actual_delivery_date ?? $purchaseOrder->order_date, $supplier->payment_terms);

        $bill = SupplierBill::create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'restaurant_id' => $purchaseOrder->restaurant_id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'bill_date' => $purchaseOrder->actual_delivery_date ?? $purchaseOrder->order_date,
            'due_date' => $dueDate,
            'subtotal' => $purchaseOrder->subtotal,
            'tax_amount' => $purchaseOrder->tax_amount,
            'discount_amount' => $purchaseOrder->discount_amount,
            'total_amount' => $purchaseOrder->total_amount,
            'outstanding_amount' => $purchaseOrder->total_amount,
            'status' => 'pending'
        ]);

        return redirect()->route('bills.show', $bill->bill_id)
            ->with('success', 'Bill created from purchase order successfully.');
    }

    public function edit($id)
    {
        $bill = SupplierBill::with('supplier')->findOrFail($id);

        if ($bill->status === 'paid') {
            return redirect()->route('bills.show', $id)
                ->with('error', 'Cannot edit paid bills.');
        }

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('supplier_name')
            ->get();

        return Inertia::render('Bills/Edit', [
            'bill' => $bill,
            'suppliers' => $suppliers
        ]);
    }

    public function update(Request $request, $id)
    {
        $bill = SupplierBill::findOrFail($id);

        if ($bill->status === 'paid') {
            return redirect()->back()
                ->with('error', 'Cannot update paid bills.');
        }

        $validated = $request->validate([
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $oldTotal = $bill->total_amount;
        $newTotal = $validated['total_amount'];
        $paidAmount = $bill->paid_amount;

        $bill->update([
            'supplier_invoice_number' => $validated['supplier_invoice_number'],
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'discount_amount' => $validated['discount_amount'],
            'total_amount' => $newTotal,
            'outstanding_amount' => $newTotal - $paidAmount,
            'notes' => $validated['notes']
        ]);

        if ($request->hasFile('attachment')) {
            if ($bill->attachment_path) {
                Storage::disk('public')->delete($bill->attachment_path);
            }
            $path = $request->file('attachment')->store('supplier-bills', 'public');
            $bill->update(['attachment_path' => $path]);
        }

        return redirect()->route('bills.show', $bill->bill_id)
            ->with('success', 'Bill updated successfully.');
    }

    public function markOverdue()
    {
        $overdueBills = SupplierBill::where('due_date', '<', now())
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get();

        foreach ($overdueBills as $bill) {
            $bill->update(['status' => 'overdue']);
        }

        return response()->json(['message' => 'Overdue bills updated.']);
    }

    private function calculateDueDate($billDate, $paymentTerms)
    {
        $date = Carbon::parse($billDate);
        
        switch ($paymentTerms) {
            case 'COD':
                return $date;
            case 'NET_7':
                return $date->addDays(7);
            case 'NET_15':
                return $date->addDays(15);
            case 'NET_30':
                return $date->addDays(30);
            case 'NET_60':
                return $date->addDays(60);
            case 'NET_90':
                return $date->addDays(90);
            default:
                return $date->addDays(30);
        }
    }

    public function downloadAttachment($id)
    {
        $bill = SupplierBill::findOrFail($id);
        
        if (!$bill->attachment_path || !Storage::disk('public')->exists($bill->attachment_path)) {
            return redirect()->back()->with('error', 'Attachment not found.');
        }

        return Storage::disk('public')->download($bill->attachment_path);
    }
}
