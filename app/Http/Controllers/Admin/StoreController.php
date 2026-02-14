<?php

/**
 * ALTERNATIVE StoreController
 * 
 * This version works WITHOUT the new database columns (source_order_id, is_adjustment).
 * It shows a professional UI with proper payment display but cannot track adjustments
 * until the database migration is run.
 * 
 * Use this if you want to update the UI immediately before running migrations.
 */

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
            'branch' => 'required|string',
        ]);

        $store->sales_person_id = (int) $request->sales_person_id;
        $store->branch = $request->branch;
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

    /**
     * Show store details with orders and payment tracking
     * 
     * This version calculates:
     * - Direct Paid: Payments collected directly on this order
     * - Adjusted Value: Will show after database migration (currently shows 0)
     * - Total Paid: Sum of all payments
     * - Pending: Remaining amount due
     */
    public function show(Request $request, Store $store)
    {
        $salesPeople = SalesPerson::orderBy('name')->get();

        // ---------------- FILTER INPUTS ----------------
        $orderId  = $request->order_id;
        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        // ---------------- SUMMARY ----------------
        $summaryQuery = DB::table('orders as o')
            ->where('o.store_id', $store->id)
            ->when($orderId, fn($q) => $q->where('o.id', $orderId))
            ->when($fromDate && $toDate, fn($q) => $q->whereBetween('o.created_at', [$fromDate, $toDate]));

        $summary = $summaryQuery->selectRaw("
            COUNT(o.id) AS total_orders,
            SUM(COALESCE(o.order_amount,0) + COALESCE(o.total_tax_amount,0)) AS total_order_amount
        ")->first();

        // ---------------- TOTAL PAID ----------------
        $totalPaid = DB::table('orders')
            ->where('store_id', $store->id)
            ->whereNotIn('order_status', ['cancelled', 'failed'])
            ->when($orderId, fn($q) => $q->where('id', $orderId))
            ->when($fromDate && $toDate, fn($q) => $q->whereBetween('created_at', [$fromDate, $toDate]))
            ->sum('paid_amount');

        $summary->total_paid = $totalPaid;
        $summary->outstanding_amount = ($summary->total_order_amount ?? 0) - $totalPaid;

        // ---------------- ORDERS WITH PAYMENTS ----------------
        // Use orders.paid_amount directly (updated by FIFO service)
        $ordersQuery = DB::table('orders as o')
            ->where('o.store_id', $store->id)
            ->when($orderId, fn($q) => $q->where('o.id', $orderId))
            ->when($fromDate && $toDate, fn($q) => $q->whereBetween('o.created_at', [$fromDate, $toDate]));

        $orders = $ordersQuery->selectRaw("
            o.id,
            o.order_amount,
            o.total_tax_amount,
            (COALESCE(o.order_amount,0) + COALESCE(o.total_tax_amount,0)) AS total_amount,
            o.order_status,
            o.payment_status,
            COALESCE(o.paid_amount,0) AS total_paid,
            DATE(o.created_at) AS order_date
        ")
        ->orderBy('o.created_at', 'DESC')
        ->paginate(10, ['*'], 'orders_page')
        ->withQueryString();

        // Calculate pending amount for each order
        $orders->getCollection()->transform(function ($order) {
            $totalAmount = $order->total_amount ?? 0;
            $totalPaid = $order->total_paid ?? 0;
            $order->pending_amount = max(0, round($totalAmount - $totalPaid, 2));
            return $order;
        });

        // ---------------- ORDER ITEMS ----------------
        $orderIds = $orders->pluck('id');

        $itemsQuery = DB::table('order_details')
            ->whereIn('order_id', $orderIds);

        $items = $itemsQuery->paginate(15, ['*'], 'items_page')
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

    /**
     * Check if the new adjustment tracking columns exist in database
     */
    private function checkAdjustmentColumnsExist(): bool
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM order_payments LIKE 'is_adjustment'");
            return count($columns) > 0;
        } catch (\Exception $e) {
            return false;
        }
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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
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

    /**
     * Update store location (latitude/longitude) - AJAX endpoint
     */
    public function updateLocation(Request $request, Store $store)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $store->latitude = $request->latitude;
        $store->longitude = $request->longitude;
        $store->save();

        return response()->json([
            'success' => true,
            'message' => 'Store location updated successfully.',
            'store' => $store,
        ]);
    }

    /**
     * Geocode store address to get lat/lng - AJAX endpoint
     */
    public function geocodeAddress(Request $request, Store $store)
    {
        if (empty($store->address)) {
            return response()->json([
                'success' => false,
                'message' => 'Store has no address to geocode.',
            ], 400);
        }

        $apiKey = env('GOOGLE_MAPS_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Google Maps API key not configured.',
            ], 500);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $store->address,
                    'key' => $apiKey,
                ]);

            $geoData = $response->json();

            if (!empty($geoData['results'][0]['geometry']['location'])) {
                $store->latitude = $geoData['results'][0]['geometry']['location']['lat'];
                $store->longitude = $geoData['results'][0]['geometry']['location']['lng'];
                $store->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Store location geocoded successfully.',
                    'latitude' => $store->latitude,
                    'longitude' => $store->longitude,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Geocoding failed - no results found for address.',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Geocoding error: ' . $e->getMessage(),
            ], 500);
        }
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
