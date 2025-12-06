<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::latest()->paginate(10);
        return view('inventories.index', compact('inventories'));
    }

    public function create()
    {
        return view('inventories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:inventories,email',
            'phone' => 'nullable|string',
            'password' => 'required|min:6',
            'status' => 'required|in:active,inactive',
            'idproof' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $data = $request->all();

        if ($request->hasFile('idproof')) {
            $data['idproof'] = $request->file('idproof')->store('idproofs', 'public');
        }

        Inventory::create($data);

        return redirect()->route('admin.inventories.index')->with('success', 'Inventory created successfully.');
    }

    public function edit(Inventory $inventory)
    {
        return view('inventories.edit', compact('inventory'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:inventories,email,' . $inventory->id,
            'phone' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'idproof' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $data = $request->all();

        if ($request->hasFile('idproof')) {
            $data['idproof'] = $request->file('idproof')->store('idproofs', 'public');
        }

        $inventory->update($data);

        return redirect()->route('admin.inventories.index')->with('success', 'Inventory updated successfully.');
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return redirect()->route('admin.inventories.index')->with('success', 'Inventory deleted successfully.');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'password' => 'required|min:6',
        ]);

        $inventory = Inventory::findOrFail($request->inventory_id);
        $inventory->password = $request->password; // Don't bcrypt, let model handle it
        $inventory->save();

        return redirect()->route('admin.inventories.index')->with('success', 'Password reset successfully.');
    }


}
