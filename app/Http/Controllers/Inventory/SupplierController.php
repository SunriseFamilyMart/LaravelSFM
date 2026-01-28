<?php

namespace App\Http\Controllers\Inventory;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(10);
        return view('inventory.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('inventory.suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            // ✅ Mandatory fields
            'name'       => 'required|string|max:255',
            'phone'      => 'required|string|max:20|unique:suppliers,phone',
            'gst_number' => 'required|string|max:50',
            'address'    => 'required|string',

            // ❌ Optional fields
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'alternate_number' => 'nullable|string|max:20',

            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only([
            'name',
            'email',
            'phone',
            'alternate_number',
            'gst_number',
            'address',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('suppliers', 'public');
        }

        Supplier::create($data);

        return redirect()
            ->route('inventory.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        return view('inventory.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            // ✅ Mandatory
            'name'       => 'required|string|max:255',
            'phone'      => 'required|string|max:20|unique:suppliers,phone,' . $supplier->id,
            'gst_number' => 'required|string|max:50',
            'address'    => 'required|string',

            // ❌ Optional
            'email' => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id,
            'alternate_number' => 'nullable|string|max:20',

            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only([
            'name',
            'email',
            'phone',
            'alternate_number',
            'gst_number',
            'address',
        ]);

        if ($request->hasFile('image')) {
            if ($supplier->image) {
                Storage::disk('public')->delete($supplier->image);
            }

            $data['image'] = $request->file('image')
                ->store('suppliers', 'public');
        }

        $supplier->update($data);

        return redirect()
            ->route('inventory.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->image) {
            Storage::disk('public')->delete($supplier->image);
        }

        $supplier->delete();

        return redirect()
            ->route('inventory.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}



