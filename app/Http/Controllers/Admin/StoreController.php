<?php


namespace App\Http\Controllers\Admin;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Conversation;
use App\Models\SalesPerson;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{

    /**
     * Pending store self-registrations (needs admin approval + salesperson assignment).
     */
    public function pendingSelf(Request $request)
    {
        $query = Store::query()
            ->where('registration_source', 'self')
            ->where('approval_status', 'pending');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $stores = $query->latest()->paginate(10)->appends(['search' => $request->search]);
        $salesPeople = SalesPerson::orderBy('name')->get();

        return view('stores.pending_self', compact('stores', 'salesPeople'));
    }

    /**
     * Approve a self-registered store and assign salesperson.
     */
    public function approveSelf(Request $request, Store $store)
    {
        $request->validate([
            'sales_person_id' => 'required|exists:sales_people,id',
        ]);

        $store->sales_person_id = (int) $request->sales_person_id;
        $store->approval_status = 'approved';
        $store->can_login = true;
        $store->approved_by = auth('admin')->id();
        $store->approved_at = now();
        $store->save();

        return redirect()->back()->with('success', 'Store approved and salesperson assigned.');
    }

    /**
     * Reject a self-registered store.
     */
    public function rejectSelf(Request $request, Store $store)
    {
        $store->approval_status = 'rejected';
        $store->can_login = false;
        $store->approved_by = auth('admin')->id();
        $store->approved_at = now();
        $store->save();

        return redirect()->back()->with('success', 'Store rejected.');
    }


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

  public function show(Request $request, Store $store)
{
    $salesPeople = SalesPerson::orderBy('name')->get();

    $orderId  = $request->order_id;
    $fromDate = $request->from_date;
    $toDate   = $request->to_date;

    // ---------------- SUMMARY ----------------
    $summaryQuery = DB::table('orders')
        ->where('store_id', $store->id)
        ->when($orderId, fn($q) => $q->where('id', $orderId))
        ->when($fromDate && $toDate, fn($q) => $q->whereBetween('created_at', [$fromDate, $toDate]));

    $summary = $summaryQuery->selectRaw("
        COUNT(id) AS total_orders,
        SUM(COALESCE(order_amount,0) + COALESCE(total_tax_amount,0)) AS total_order_amount,
        SUM(COALESCE(paid_amount,0)) AS total_paid
    ")->first();

    $summary->outstanding_amount =
        ($summary->total_order_amount ?? 0) - ($summary->total_paid ?? 0);

    // ---------------- ORDERS ----------------
    $orders = DB::table('orders')
        ->where('store_id', $store->id)
        ->when($orderId, fn($q) => $q->where('id', $orderId))
        ->when($fromDate && $toDate, fn($q) => $q->whereBetween('created_at', [$fromDate, $toDate]))
        ->selectRaw("
            id,
            order_amount,
            total_tax_amount,
            paid_amount,
            payment_status,
            order_status,
            (COALESCE(order_amount,0) + COALESCE(total_tax_amount,0)) AS total_amount,
            DATE(created_at) AS order_date
        ")
        ->orderBy('created_at', 'DESC')
        ->paginate(10, ['*'], 'orders_page')
        ->withQueryString();

    $orders->getCollection()->transform(function ($order) {
        $order->pending_amount = max(
            0,
            round(($order->total_amount ?? 0) - ($order->paid_amount ?? 0), 2)
        );
        return $order;
    });

    // ---------------- ORDER ITEMS ----------------
    $orderIds = $orders->pluck('id');

    $items = DB::table('order_details')
        ->whereIn('order_id', $orderIds)
        ->paginate(15, ['*'], 'items_page')
        ->withQueryString();

    $items->getCollection()->transform(function ($item) {
        $item->product = json_decode($item->product_details, true);
        return $item;
    });

    return view('stores.show', compact(
        'store',
        'salesPeople',
        'orders',
        'items',
        'summary'
    ));
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

