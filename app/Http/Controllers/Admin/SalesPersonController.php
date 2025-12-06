<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalesPerson;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class SalesPersonController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesPerson::query();

        // Search by name or phone number
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $salesPeople = $query->orderBy('id', 'desc')->paginate(10);

        // Keep the search query in pagination links
        $salesPeople->appends($request->all());

        return view('sales_person.index', compact('salesPeople'));
    }



    public function create()
    {
        return view('sales_person.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:sales_people,email',
            'password' => 'required|min:6', // <-- ADD THIS
            'id_proof' => 'nullable|image|mimes:jpg,jpeg,png',
            'person_photo' => 'nullable|image|mimes:jpg,jpeg,png',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_number' => 'nullable|string',
        ]);

        $data = $request->all();

        // Hash Password
        $data['password'] = Hash::make($request->password); // <-- IMPORTANT

        if ($request->hasFile('id_proof')) {
            $data['id_proof'] = $request->file('id_proof')->store('id_proofs', 'public');
        }

        if ($request->hasFile('person_photo')) {
            $data['person_photo'] = $request->file('person_photo')->store('person_photos', 'public');
        }

        SalesPerson::create($data);

        return redirect()->route('admin.sales-person.index')->with('success', 'Sales person created successfully');
    }

    public function edit(SalesPerson $salesPerson)
    {
        return view('sales_person.edit', compact('salesPerson'));
    }

    public function update(Request $request, SalesPerson $salesPerson)
    {
        $request->validate([
            'name' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:sales_people,email,' . $salesPerson->id,
            'password' => 'nullable|min:6', // <-- OPTIONAL PASSWORD
            'id_proof' => 'nullable|image|mimes:jpg,jpeg,png',
            'person_photo' => 'nullable|image|mimes:jpg,jpeg,png',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_number' => 'nullable|string',
        ]);

        $data = $request->all();

        // Update password only if user enters new password
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']); // Prevent overwriting existing password with null
        }

        if ($request->hasFile('id_proof')) {
            $data['id_proof'] = $request->file('id_proof')->store('id_proofs', 'public');
        }

        if ($request->hasFile('person_photo')) {
            $data['person_photo'] = $request->file('person_photo')->store('person_photos', 'public');
        }

        $salesPerson->update($data);

        return redirect()->route('admin.sales-person.index')->with('success', 'Sales person updated successfully');
    }


    public function destroy(SalesPerson $salesPerson)
    {
        $salesPerson->delete();
        return redirect()->route('admin.sales-person.index')->with('success', 'Sales person deleted successfully');
    }
}

