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

   public function show(Request $request, Store $store)
{
    $salesPeople = SalesPerson::orderBy('name')->get();

    // ---------------- FILTER INPUTS ----------------
    $orderId  = $request->order_id;
    $fromDate = $request->from_date;
    $toDate   = $request->to_date;

    // ---------------- SUMMARY (FROM LEDGER ONLY) ----------------
    $summary = DB::table('store_ledgers')
        ->where('store_id', $store->id)
        ->selectRaw("
            SUM(debit)  AS total_debit,
            SUM(credit) AS total_credit
        ")
        ->first();

    $summary->total_order_amount = $summary->total_debit ?? 0;
    $summary->total_paid = $summary->total_credit ?? 0;
    $summary->outstanding_amount = max(
        0,
        ($summary->total_order_amount - $summary->total_paid)
    );

    // ---------------- ORDERS + PAYMENTS (LEDGER BASED) ----------------
    $orders = DB::table('orders as o')
        ->leftJoin('store_ledgers as sl', function ($join) {
            $join->on('o.id', '=', 'sl.reference_id')
                ->whereIn('sl.reference_type', ['order', 'payment', 'credit_note']);
        })
        ->where('o.store_id', $store->id)
        ->when($orderId, fn($q) => $q->where('o.id', $orderId))
        ->when($fromDate && $toDate, fn($q) =>
            $q->whereBetween('o.created_at', [$fromDate, $toDate])
        )
        ->selectRaw("
            o.id,
            o.order_status,
            DATE(o.created_at) AS order_date,

            (COALESCE(o.order_amount,0) + COALESCE(o.total_tax_amount,0)) AS total_amount,

            SUM(CASE WHEN sl.reference_type = 'order'
                     THEN sl.debit ELSE 0 END) AS order_debit,

            SUM(CASE WHEN sl.reference_type IN ('payment','credit_note')
                     THEN sl.credit ELSE 0 END) AS total_paid
        ")
        ->groupBy(
            'o.id',
            'o.order_amount',
            'o.total_tax_amount',
            'o.order_status',
            'o.created_at'
        )
        ->orderBy('o.created_at', 'DESC')
        ->paginate(10, ['*'], 'orders_page')
        ->withQueryString();

    // ---------------- DERIVE PAYMENT STATUS ----------------
    $orders->getCollection()->transform(function ($order) {
        $order->pending_amount = max(
            0,
            round(($order->total_amount ?? 0) - ($order->total_paid ?? 0), 2)
        );

        if ($order->total_paid >= $order->total_amount) {
            $order->payment_status = 'paid';
        } elseif ($order->total_paid > 0) {
            $order->payment_status = 'partial';
        } else {
            $order->payment_status = 'unpaid';
        }

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

