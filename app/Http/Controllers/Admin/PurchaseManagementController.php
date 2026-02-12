<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseMaster;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseDelivery;
use App\Models\PurchaseDeliveryItem;
use App\Models\Supplier;
use App\Model\Product;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use function App\CentralLogics\translate;

class PurchaseManagementController extends Controller
{
    /**
     * Display a listing of purchases with filters
     */
    public function index(Request $request)
    {
        $query = PurchaseMaster::with(['supplier', 'items', 'payments']);

        // Apply filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $query->where('pr_number', 'like', '%' . $request->search . '%');
        }

        // Get purchases with pagination
        $purchases = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate summary statistics
        $allPurchases = PurchaseMaster::all();
        $totalPurchases = $allPurchases->count();
        $totalAmount = $allPurchases->sum('total_amount');
        $totalPaid = $allPurchases->sum('paid_amount');
        $totalOutstanding = $allPurchases->sum('balance_amount');

        // Status counts
        $statusCounts = [
            'draft' => PurchaseMaster::where('status', 'draft')->count(),
            'ordered' => PurchaseMaster::where('status', 'ordered')->count(),
            'partial_delivered' => PurchaseMaster::where('status', 'partial_delivered')->count(),
            'delivered' => PurchaseMaster::where('status', 'delivered')->count(),
            'cancelled' => PurchaseMaster::where('status', 'cancelled')->count(),
            'delayed' => PurchaseMaster::where('status', 'delayed')->count(),
        ];

        $suppliers = Supplier::all();

        return view('admin-views.purchase-management.index', compact(
            'purchases',
            'suppliers',
            'totalPurchases',
            'totalAmount',
            'totalPaid',
            'totalOutstanding',
            'statusCounts'
        ));
    }

    /**
     * Show the form for creating a new purchase
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::where('status', 1)->get();

        return view('admin-views.purchase-management.create', compact('suppliers', 'products'));
    }

    /**
     * Store a newly created purchase in storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchased_by' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.gst_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Create purchase master
            $purchase = PurchaseMaster::create([
                'supplier_id' => $request->supplier_id,
                'purchased_by' => $request->purchased_by,
                'purchase_date' => $request->purchase_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'invoice_number' => $request->invoice_number,
                'status' => 'ordered',
                'notes' => $request->notes,
            ]);

            // Create purchase items
            foreach ($request->items as $itemData) {
                $gstPercent = $itemData['gst_percent'] ?? 0;
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $lineTotal = $quantity * $unitPrice;
                $gstAmount = ($lineTotal * $gstPercent) / 100;
                $total = $lineTotal + $gstAmount;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'gst_percent' => $gstPercent,
                    'gst_amount' => $gstAmount,
                    'total' => $total,
                    'status' => 'pending',
                ]);
            }

            // Calculate totals
            $purchase->calculateTotals();

            // Handle initial payment if provided
            if ($request->filled('initial_payment_amount') && $request->initial_payment_amount > 0) {
                $payment = PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount' => $request->initial_payment_amount,
                    'payment_date' => $request->initial_payment_date ?? now(),
                    'payment_mode' => $request->initial_payment_mode ?? 'cash',
                    'reference_number' => $request->initial_payment_reference,
                    'created_by' => auth()->id(),
                ]);

                $purchase->paid_amount = $request->initial_payment_amount;
                $purchase->balance_amount = $purchase->total_amount - $purchase->paid_amount;
                $purchase->save();
                $purchase->updatePaymentStatus();
            }

            // Log to audit
            AuditLog::create([
                'user_id' => auth()->id(),
                'branch' => null,
                'action' => 'purchase_created',
                'table_name' => 'purchases_master',
                'record_id' => $purchase->id,
                'old_values' => null,
                'new_values' => json_encode($purchase->toArray()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            Toastr::success(translate('Purchase created successfully!'));
            return redirect()->route('admin.purchase.show', $purchase->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to create purchase: ') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Display the specified purchase
     */
    public function show($id)
    {
        $purchase = PurchaseMaster::with([
            'supplier',
            'items.product',
            'items.deliveryItems',
            'payments.creator',
            'deliveries.items.product'
        ])->findOrFail($id);

        $auditLogs = $purchase->auditLogs();

        return view('admin-views.purchase-management.show', compact('purchase', 'auditLogs'));
    }

    /**
     * Show the form for editing the specified purchase
     */
    public function edit($id)
    {
        $purchase = PurchaseMaster::with('items')->findOrFail($id);

        // Only allow editing if status is draft or ordered
        if (!in_array($purchase->status, ['draft', 'ordered'])) {
            Toastr::error(translate('Cannot edit purchase in current status'));
            return redirect()->route('admin.purchase.show', $id);
        }

        $suppliers = Supplier::all();
        $products = Product::where('status', 1)->get();

        return view('admin-views.purchase-management.edit', compact('purchase', 'suppliers', 'products'));
    }

    /**
     * Update the specified purchase in storage
     */
    public function update(Request $request, $id)
    {
        $purchase = PurchaseMaster::findOrFail($id);

        // Only allow editing if status is draft or ordered
        if (!in_array($purchase->status, ['draft', 'ordered'])) {
            Toastr::error(translate('Cannot edit purchase in current status'));
            return redirect()->route('admin.purchase.show', $id);
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchased_by' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.gst_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $oldValues = $purchase->toArray();

            // Update purchase master
            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'purchased_by' => $request->purchased_by,
                'purchase_date' => $request->purchase_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'invoice_number' => $request->invoice_number,
                'notes' => $request->notes,
            ]);

            // Delete old items and create new ones
            $purchase->items()->delete();

            foreach ($request->items as $itemData) {
                $gstPercent = $itemData['gst_percent'] ?? 0;
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $lineTotal = $quantity * $unitPrice;
                $gstAmount = ($lineTotal * $gstPercent) / 100;
                $total = $lineTotal + $gstAmount;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'gst_percent' => $gstPercent,
                    'gst_amount' => $gstAmount,
                    'total' => $total,
                    'status' => 'pending',
                ]);
            }

            // Recalculate totals
            $purchase->calculateTotals();

            // Log to audit
            AuditLog::create([
                'user_id' => auth()->id(),
                'branch' => null,
                'action' => 'purchase_updated',
                'table_name' => 'purchases_master',
                'record_id' => $purchase->id,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($purchase->fresh()->toArray()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            Toastr::success(translate('Purchase updated successfully!'));
            return redirect()->route('admin.purchase.show', $purchase->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to update purchase: ') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Update purchase status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,ordered,partial_delivered,delivered,cancelled,delayed',
            'reason' => 'nullable|string',
        ]);

        $purchase = PurchaseMaster::findOrFail($id);
        $oldStatus = $purchase->status;

        $purchase->status = $request->status;
        $purchase->save();

        // Log to audit
        AuditLog::create([
            'user_id' => auth()->id(),
            'branch' => null,
            'action' => 'purchase_status_changed',
            'table_name' => 'purchases_master',
            'record_id' => $purchase->id,
            'old_values' => json_encode(['status' => $oldStatus]),
            'new_values' => json_encode(['status' => $request->status, 'reason' => $request->reason]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Toastr::success(translate('Purchase status updated successfully!'));
        return back();
    }

    /**
     * Add payment to purchase
     */
    public function addPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|string|in:cash,bank_transfer,upi,cheque',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $purchase = PurchaseMaster::findOrFail($id);

        // Check if payment amount exceeds balance
        if ($request->amount > $purchase->balance_amount) {
            Toastr::error(translate('Payment amount cannot exceed balance amount'));
            return back();
        }

        DB::beginTransaction();
        try {
            $payment = PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_mode' => $request->payment_mode,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Update purchase amounts
            $purchase->paid_amount += $request->amount;
            $purchase->balance_amount = $purchase->total_amount - $purchase->paid_amount;
            $purchase->save();
            $purchase->updatePaymentStatus();

            // Log to audit
            AuditLog::create([
                'user_id' => auth()->id(),
                'branch' => null,
                'action' => 'purchase_payment_added',
                'table_name' => 'purchases_master',
                'record_id' => $purchase->id,
                'old_values' => null,
                'new_values' => json_encode($payment->toArray()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            Toastr::success(translate('Payment recorded successfully!'));
            return back();

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to record payment: ') . $e->getMessage());
            return back();
        }
    }

    /**
     * Record delivery for purchase
     */
    public function recordDelivery(Request $request, $id)
    {
        $request->validate([
            'delivery_date' => 'required|date',
            'received_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.quantity_received' => 'required|integer|min:1',
        ]);

        $purchase = PurchaseMaster::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Create delivery record
            $delivery = PurchaseDelivery::create([
                'purchase_id' => $purchase->id,
                'delivery_date' => $request->delivery_date,
                'received_by' => $request->received_by,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Process each item
            foreach ($request->items as $itemData) {
                if ($itemData['quantity_received'] > 0) {
                    $purchaseItem = PurchaseItem::findOrFail($itemData['purchase_item_id']);

                    // Validate quantity
                    $remainingQty = $purchaseItem->quantity - $purchaseItem->received_qty;
                    if ($itemData['quantity_received'] > $remainingQty) {
                        throw new \Exception("Received quantity cannot exceed pending quantity for item");
                    }

                    // Create delivery item
                    PurchaseDeliveryItem::create([
                        'purchase_delivery_id' => $delivery->id,
                        'purchase_item_id' => $purchaseItem->id,
                        'product_id' => $purchaseItem->product_id,
                        'quantity_received' => $itemData['quantity_received'],
                    ]);

                    // Update purchase item received quantity
                    $purchaseItem->received_qty += $itemData['quantity_received'];
                    $purchaseItem->save();
                }
            }

            // Update delivery status
            $purchase->updateDeliveryStatus();

            // Log to audit
            AuditLog::create([
                'user_id' => auth()->id(),
                'branch' => null,
                'action' => 'purchase_delivery_recorded',
                'table_name' => 'purchases_master',
                'record_id' => $purchase->id,
                'old_values' => null,
                'new_values' => json_encode([
                    'delivery_id' => $delivery->id,
                    'items' => $request->items,
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            Toastr::success(translate('Delivery recorded successfully!'));
            return back();

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to record delivery: ') . $e->getMessage());
            return back();
        }
    }

    /**
     * Get audit log for a purchase
     */
    public function getAuditLog($id)
    {
        $purchase = PurchaseMaster::findOrFail($id);
        $auditLogs = $purchase->auditLogs();

        if (request()->wantsJson()) {
            return response()->json($auditLogs);
        }

        return view('admin-views.purchase-management.partials.audit-log', compact('auditLogs'));
    }
}
