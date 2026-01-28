<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPurchase;
use App\Models\Supplier;
use App\Model\Product;
use Illuminate\Http\Request;

class AdminPurchaseController extends Controller
{
    // LIST
   public function index(Request $request)
{
    $query = AdminPurchase::with(['supplier','product']);

    // Supplier filter
    if ($request->supplier_id) {
        $query->where('supplier_id', $request->supplier_id);
    }

    // Product filter
    if ($request->product_id) {
        $query->where('product_id', $request->product_id);
    }

    // Status filter
    if ($request->status) {
        $query->where('status', $request->status);
    }

    $purchases = $query->latest()->get();

    $suppliers = Supplier::orderBy('name')->get();
    $products  = Product::orderBy('name')->get();

    return view('admin.purchase.index', compact(
        'purchases',
        'suppliers',
        'products'
    ));
}


    // CREATE FORM
   public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products  = Product::select('id','name','price','tax')->get();

        return view('admin.purchase.create', compact('suppliers','products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'     => 'required|exists:suppliers,id',
            'product_id'      => 'required|exists:products,id',
            'purchased_by'    => 'required|string',
            'purchase_date'   => 'required|date',
            'purchase_price'  => 'required|numeric',
            'quantity'        => 'required|numeric',
        ]);

        $taxRate = $request->tax ?? 0;
        $mrp     = $request->purchase_price + ($request->purchase_price * $taxRate / 100);
        $total   = $request->purchase_price * $request->quantity;
        $paid    = $request->paid_amount ?? 0;

        AdminPurchase::create([
            'purchase_id'            => 'PR-' . (AdminPurchase::max('id') + 1),
            'supplier_id'            => $request->supplier_id,
            'product_id'             => $request->product_id,
            'purchased_by'           => $request->purchased_by,
            'purchase_date'          => $request->purchase_date,
            'expected_delivery_date' => $request->expected_delivery_date,
            'actual_delivery_date'   => $request->actual_delivery_date,
            'invoice_number'         => $request->invoice_number,
            'status'                 => $request->status,
            'purchase_price'         => $request->purchase_price,
            'quantity'               => $request->quantity,
            'mrp'                    => $mrp,
            'total_amount'           => $total,
            'paid_amount'            => $paid,
            'balance_amount'         => $total - $paid,
            'payment_mode'           => $request->payment_mode,
            'comments'               => $request->comments,
        ]);

        return redirect()->route('admin.purchase.index')
            ->with('success', 'Purchase Created Successfully');
    }

    // EDIT FORM
    public function edit($id)
    {
        $purchase  = AdminPurchase::findOrFail($id);
        $suppliers = Supplier::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();

        return view('admin.purchase.edit', compact('purchase','suppliers','products'));
    }

    // UPDATE
public function update(Request $request, $id)
{
    $purchase = AdminPurchase::findOrFail($id);

    $request->validate([
        'invoice_number' => 'required',
        'expected_delivery_date' => 'required|date',
        'paid_amount' => 'required|numeric',
        'status' => 'required',
        'payment_mode' => 'required',
    ]);

    $balance = $purchase->total_amount - $request->paid_amount;

    $purchase->update([
        'invoice_number' => $request->invoice_number,
        'expected_delivery_date' => $request->expected_delivery_date,
        'paid_amount' => $request->paid_amount,
        'balance_amount' => $balance,
        'status' => $request->status,
        'payment_mode' => $request->payment_mode,
    ]);

    return redirect()
        ->route('admin.purchase.index')
        ->with('success', 'Purchase Updated Successfully');
}


}
