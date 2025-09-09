<?php

namespace App\Http\Controllers;

use App\Models\SupplierBill;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class SupplierBillController extends Controller
{
    private BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

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

    /**
     * Auto-generate bill from purchase order using BillingService
     */
    public function autoGenerateFromPurchaseOrder(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,purchase_order_id',
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->billingService->generateBillFromPurchaseOrder(
                $validated['purchase_order_id'],
                array_filter($validated)
            );

            return redirect()->route('bills.show', $result['bill']->bill_id)
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('Auto-generate bill failed', [
                'purchase_order_id' => $validated['purchase_order_id'],
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to generate bill: ' . $e->getMessage());
        }
    }

    /**
     * Process complete workflow: Receive inventory + Generate bill
     */
    public function processReceived(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,purchase_order_id',
            'supplier_invoice_number' => 'nullable|string|max:255',
            'bill_date' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->billingService->processReceivedPurchaseOrder(
                $validated['purchase_order_id'],
                array_filter($validated)
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'inventory_result' => $result['inventory_result'],
                    'bill' => $result['bill_result']['bill'],
                    'redirect_url' => route('bills.show', $result['bill_result']['bill']->bill_id)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Process received purchase order failed', [
                'purchase_order_id' => $validated['purchase_order_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate bills in bulk for multiple purchase orders
     */
    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_ids' => 'required|array|min:1',
            'purchase_order_ids.*' => 'exists:purchase_orders,purchase_order_id',
            'global_options' => 'nullable|array',
            'global_options.tax_rate' => 'nullable|numeric|min:0|max:100',
            'global_options.notes' => 'nullable|string|max:1000'
        ]);

        try {
            $result = $this->billingService->generateBulkBills(
                $validated['purchase_order_ids'],
                $validated['global_options'] ?? []
            );

            $message = $result['success'] 
                ? "Successfully generated {$result['success_count']} bills"
                : "Generated {$result['success_count']} bills with {$result['error_count']} errors";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk bill generation failed', [
                'purchase_order_ids' => $validated['purchase_order_ids'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get billing analytics and dashboard data
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        try {
            $restaurantId = auth()->user()->restaurantData->id;
            $analytics = $this->billingService->getBillingAnalytics($restaurantId, $validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $analytics
                ]);
            }

            return Inertia::render('Bills/Analytics', [
                'analytics' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Billing analytics failed', ['error' => $e->getMessage()]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load analytics: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to load analytics');
        }
    }

    /**
     * Auto-mark overdue bills (can be run via cron)
     */
    public function autoMarkOverdue()
    {
        try {
            $restaurantId = auth()->user()->restaurantData->id ?? null;
            $result = $this->billingService->markOverdueBills($restaurantId);

            return response()->json([
                'success' => true,
                'message' => "Marked {$result['marked_count']} bills as overdue",
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-mark overdue failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark overdue bills: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick payment recording
     */
    public function quickPayment(Request $request, $billId)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,online,other',
            'payment_date' => 'nullable|date',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $paymentData = array_merge($validated, [
                'payment_date' => $validated['payment_date'] ?? now(),
                'created_by_user_id' => auth()->id()
            ]);

            $result = $this->billingService->recordPayment($billId, $paymentData);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'payment' => $result['payment'],
                    'bill' => $result['bill']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Quick payment failed', [
                'bill_id' => $billId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
