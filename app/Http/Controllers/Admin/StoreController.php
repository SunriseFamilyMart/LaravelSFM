<?php


namespace App\Http\Controllers\Admin;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Conversation;
use App\Models\SalesPerson;

class StoreController extends Controller
{


    public function updateSalesPerson(Request $request, Store $store)
    {
        $request->validate([
            'sales_person_id' => 'nullable|exists:sales_people,id',
        ]);

        $store->sales_person_id = $request->input('sales_person_id');
        $store->save();

        return redirect()->back()->with('success', 'Sales person updated successfully.');
    }

    public function index(Request $request)
    {
        $query = Store::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('store_name', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%");
        }

        $stores = $query->latest()->paginate(10)->appends(['search' => $request->search]);

        return view('stores.index', compact('stores'));
    }


    public function create()
    {
        return view('stores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'store_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'gst_number' => 'nullable|string|max:20',

        ]);

        $data = $request->all();

        if ($request->hasFile('store_photo')) {
            $data['store_photo'] = $request->file('store_photo')
                ->store('stores', 'public');
        }

        Store::create($data);

        return redirect()->route('admin.stores.index')
            ->with('success', 'Store created successfully.');
    }

    public function show(Store $store)
    {
        // Load sales people for the dropdown (ordered nicely)
        $salesPeople = SalesPerson::orderBy('name')->get();

        // Optionally eager load the relation to avoid extra queries in the view
        $store->load('salesPerson');

        return view('stores.show', compact('store', 'salesPeople'));
    }

    public function edit(Store $store)
    {
        return view('stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'store_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'gst_number' => 'nullable|string|max:20',

        ]);

        $data = $request->all();

        if ($request->hasFile('store_photo')) {
            $data['store_photo'] = $request->file('store_photo')
                ->store('stores', 'public');
        }

        $store->update($data);

        return redirect()->route('admin.stores.index')
            ->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return redirect()->route('admin.stores.index')
            ->with('success', 'Store deleted successfully.');
    }

    public function viewSales($id)
    {
        $conversations = Conversation::where('sales_person_id', $id)->latest()->get();

        return response()->json([
            'view' => view('admin-views.messages.partials.sales-conversation', compact('conversations'))->render()
        ]);
    }

}

