<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Product;
use App\Models\Purchase;
use App\Models\Supplier;

class PurchaseController extends Controller
{
public function index(Request $request)
{
$query = Purchase::with(['product', 'supplier']);


if ($request->filled('search')) {
    $search = $request->input('search');
    $query->whereHas('product', fn($q) => $q->where('name', 'like', "%$search%"))
          ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%$search%"))
          ->orWhere('invoice_number', 'like', "%$search%");
}

if ($request->filled('supplier_id')) {
    $query->where('supplier_id', $request->supplier_id);
}

if ($request->filled('product_id')) {
    $query->where('product_id', $request->product_id);
}

$purchases = $query->latest()->paginate(10);

// Calculate total amount per invoice
$invoiceTotals = $purchases->groupBy('invoice_number')->map(function ($items) {
    return $items->sum(function ($purchase) {
        return ($purchase->price * $purchase->quantity) * (1 + $purchase->gst / 100);
    });
});

// Fetch suppliers and products for filters
$suppliers = Supplier::all();
$products = Product::all();

return view('purchases.index', compact('purchases', 'suppliers', 'products', 'invoiceTotals'));

}


    public function create()
    {
        $products = Product::all();
        $suppliers = Supplier::all(); // fetch suppliers for dropdown
        return view('purchases.create', compact('products', 'suppliers'));
    }

   public function store(Request $request)
{
    $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',

        'product_id'   => 'required|array',
        'product_id.*' => 'required|exists:products,id',

        'quantity'   => 'required|array',
        'quantity.*' => 'required|integer|min:1',

        'price'      => 'required|array',
        'price.*'    => 'required|numeric|min:0',

        'gst'        => 'nullable|array',
        'gst.*'      => 'nullable|numeric|min:0',

        'description'   => 'nullable|array',
        'description.*' => 'nullable|string',

        'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        'invoice_number' => 'nullable|string|max:50',

        'branch' => 'required|string'
    ]);

    /* ---------- Upload Invoice ---------- */
    $invoicePath = null;
    if ($request->hasFile('invoice')) {
        $invoicePath = $request->file('invoice')->store('invoices', 'public');
    }

    /* ---------- Loop through products ---------- */
    foreach ($request->product_id as $index => $product_id) {

        $qty = $request->quantity[$index];
        $price = $request->price[$index];
        $gstPercent = $request->gst[$index] ?? 0;

        $amount = ($qty * $price) + (($qty * $price) * $gstPercent / 100);
        $batchId = 'BATCH-' . date('YmdHis') . '-' . $product_id;


        /* ---------- Save Purchase ---------- */
        $purchase = Purchase::create([
            'supplier_id'    => $request->supplier_id,
            'product_id'     => $product_id,
            'description'    => $request->description[$index] ?? null,
            'quantity'       => $qty,
            'price'          => $price,
            'gst'            => $gstPercent,
            'branch'         => $request->branch,
            'batch_id' => $batchId,
            'amount'         => $amount,
            'invoice_number' => $request->invoice_number,
            'invoice'        => $invoicePath,
        ]);

        /* ---------- Product ---------- */
        $product = Product::find($product_id);

        /* ---------- Convert to base unit ---------- */
        $conversionFactor = $product->conversion_factor ?? 1;
        $baseQty = $qty * $conversionFactor;

        /* ---------- Inventory Transaction ---------- */
        \App\Models\InventoryTransaction::create([
           'product_id' => $product->id,
           'branch' => $request->branch,
           'type' => 'IN',
           'quantity' => $baseQty,
           'remaining_qty' => $baseQty,
           'batch_id' => $batchId,
           'unit_price' => $price / $conversionFactor,
           'total_value' => $price * $qty,
           'reference_type' => 'purchase',
           'reference_id' => $purchase->id,
        ]);

        /* ---------- GST Ledger Entry ---------- */
        $taxable = $price * $qty;
        $gstAmount = ($taxable * $gstPercent) / 100;

        \App\Models\GstLedger::create([
            'branch' => $request->branch,
            'type' => 'INPUT',
            'taxable_amount' => $taxable,
            'gst_amount' => $gstAmount,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
        ]);

        /* ---------- Audit Log ---------- */
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'branch' => $request->branch,
            'action' => 'purchase_created',
            'table_name' => 'purchase/inventory',
            'record_id' => $purchase->id,
            'new_values' => json_encode($purchase->toArray()),
            'ip_address' => request()->ip(),
        ]);

        /* ---------- Existing Stock Update ---------- */
        $product->total_stock += $qty;
        $product->save();
    }

    return redirect()
        ->route('inventory.purchases.index')
        ->with('success', 'Purchase added and inventory updated successfully.');
}

    
}

