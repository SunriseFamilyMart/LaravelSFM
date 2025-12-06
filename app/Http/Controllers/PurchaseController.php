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

    // Optional: search/filter
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

    // Fetch suppliers and products for filters
    $suppliers = Supplier::all();
    $products = Product::all();

    return view('purchases.index', compact('purchases', 'suppliers', 'products'));
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
    ]);

    // Upload invoice once
    $invoicePath = null;
    if ($request->hasFile('invoice')) {
        $invoicePath = $request->file('invoice')->store('invoices', 'public');
    }

    // Loop through product rows
    foreach ($request->product_id as $index => $product_id) {
        Purchase::create([
            'supplier_id'    => $request->supplier_id,
            'product_id'     => $product_id,
            'description'    => $request->description[$index] ?? null,
            'quantity'       => $request->quantity[$index],
            'price'          => $request->price[$index],
            'gst'            => $request->gst[$index] ?? 0,
            'amount'         => ($request->quantity[$index] * $request->price[$index]) + 
                                (($request->quantity[$index] * $request->price[$index]) * ($request->gst[$index] ?? 0) / 100),
            'invoice_number' => $request->invoice_number,
            'invoice'        => $invoicePath,
        ]);

        // Update stock
        $product = Product::find($product_id);
        $product->total_stock += $request->quantity[$index];
        $product->save();
    }

    return redirect()
        ->route('inventory.purchases.index')
        ->with('success', 'Purchase added and stock updated successfully.');
}
    
}

