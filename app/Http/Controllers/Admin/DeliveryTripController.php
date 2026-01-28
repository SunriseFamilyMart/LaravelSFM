<?php

namespace App\Http\Controllers\Admin;

use App\Models\DeliveryTrip;
use App\Model\Order;
use App\Model\DeliveryMan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Model\BusinessSetting;
use App\Model\Branch;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Models\User;
use App\Models\OrderArea;
use App\Traits\HelperTrait;


class DeliveryTripController extends Controller
{
    use HelperTrait;
    
    public function __construct(
        private Branch $branch,
        private BusinessSetting $business_setting,
        private DeliveryMan $delivery_man,
        private Order $order,
        private OrderDetail $order_detail,
        private Product $product,
        private User $user,
        private OrderArea $orderArea
    ) {
    }

    /**
     * Show bulk assign form
     */
    public function create(Request $request)
    {
        // Load delivery men
        $deliveryMen = DeliveryMan::where('is_active', 1)->get();

        // Orders assigned to sales person but not yet to delivery man
        $orders = Order::whereNull('delivery_man_id')
            ->whereIn('order_status', ['pending', 'processing'])
            ->get();

        // Fetch assigned trips
        $query = DeliveryTrip::with('deliveryMan')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('trip_number', 'like', '%' . $request->search . '%');
        }

        $trips = $query->get()->map(function ($trip) {
            $trip->order_ids = is_string($trip->order_ids)
                ? json_decode($trip->order_ids, true)
                : (array) $trip->order_ids;
            return $trip;
        });


        /* ---------------------------------------------------------
            ✅ BULK INVOICE DOWNLOAD
           --------------------------------------------------------- */
        if ($request->boolean('download')) {

            $ordersQuery = Order::query();

            /* ✅ By Trip Number */
            if ($request->filled('trip_number')) {
                $trip = DeliveryTrip::where('trip_number', $request->trip_number)->firstOrFail();

                $ids = is_string($trip->order_ids)
                    ? json_decode($trip->order_ids, true)
                    : (array) $trip->order_ids;

                $ordersQuery->whereIn('id', $ids);
            }

            /* ✅ By Selected Orders */ 
            elseif ($request->filled('order_ids')) {
                $ordersQuery->whereIn('id', (array) $request->order_ids);
            } else {
                return back()->withErrors(['download' => 'Select a trip or choose order(s).']);
            }


            /* ✅ Load Logo */
            $logoPath = public_path('logo.png');
            $business_logo = file_exists($logoPath)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                : null;

            /* ✅ Load Paytm QR Code */
            $qrPath = public_path('qr.jpeg');

            $paytm_qr_code = file_exists($qrPath)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath))
                : null;

            /* ✅ Load Relationships */
            $bulkOrders = $ordersQuery->with([
                'details.product',
                'store',
                'salesPerson',
                'delivery_man'
            ])->get();


            /* ✅ Business Info */
            $businessInfo = [
                'business_name' => 'Sunrise Family Mart',
                'business_address' => 'Bangalore, Karnataka',
                'business_phone' => '9999999999',
                'business_email' => 'support@goldenbrown.com',
                'business_gst' => '29ABCDE1234F1Z5'
            ];


            /* ✅ Footer */
            $footer_text = optional(
                $this->business_setting->where('key', 'footer_text')->first()
            )->value ?? '';


            /* ✅ Generate PDF */
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin-views.order.bulk-invoice', [
                'orders' => $bulkOrders,
                'footer_text' => $footer_text,
                'business_logo' => $business_logo,
                'paytm_qr_code' => $paytm_qr_code,
                ...$businessInfo
            ])->setPaper('a4', 'portrait');

            return $pdf->download('invoices-' . now()->format('YmdHis') . '.pdf');
        }


        // ✅ Normal Page Render
        return view('delivery_trips.create', compact('deliveryMen', 'orders', 'trips'));
    }


    /**
     * Store bulk assignment
     */
    public function store(Request $request)
    {
        // ✅ Validate incoming request
        $request->validate([
            'delivery_man_id' => 'required|exists:delivery_men,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id'
        ]);

        // ✅ Generate a unique trip number like trip-123456
        do {
            $tripNumber = 'TRIP-' . mt_rand(100000, 999999);
        } while (DeliveryTrip::where('trip_number', $tripNumber)->exists());

        // ✅ Update selected orders
        Order::whereIn('id', $request->order_ids)
            ->update([
                'delivery_man_id' => $request->delivery_man_id,
                'trip_number' => $tripNumber,
                'order_status' => 'processing',
            ]);

        // ✅ Create delivery trip record
        DeliveryTrip::create([
            'trip_number' => $tripNumber,
            'delivery_man_id' => $request->delivery_man_id,
            'order_ids' => json_encode($request->order_ids),
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Orders assigned successfully with Trip No: ' . strtoupper($tripNumber));
    }

    /**
     * Reassign a trip to a different delivery man
     */
    public function reassign(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:delivery_trips,id',
            'delivery_man_id' => 'required|exists:delivery_men,id',
        ]);

        $trip = DeliveryTrip::findOrFail($request->trip_id);
        $oldDeliveryManId = $trip->delivery_man_id;
        
        // Update trip's delivery man
        $trip->update([
            'delivery_man_id' => $request->delivery_man_id,
        ]);

        // Update all orders in this trip
        $orderIds = is_string($trip->order_ids) 
            ? json_decode($trip->order_ids, true) 
            : (array) $trip->order_ids;

        Order::whereIn('id', $orderIds)->update([
            'delivery_man_id' => $request->delivery_man_id,
        ]);

        return redirect()->back()->with('success', 'Trip ' . $trip->trip_number . ' reassigned successfully!');
    }

    /**
     * Get trip history (orders and their status)
     */
    public function history($trip_id)
    {
        $trip = DeliveryTrip::with('deliveryMan')->findOrFail($trip_id);
        
        // Get order IDs from trip
        $orderIds = is_string($trip->order_ids) 
            ? json_decode($trip->order_ids, true) 
            : (array) $trip->order_ids;

        // Fetch orders with details
        $orders = Order::with(['store', 'details.product', 'delivery_man'])
            ->whereIn('id', $orderIds)
            ->get();

        // Build history data
        $history = $orders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'store_name' => $order->store->name ?? 'N/A',
                'store_phone' => $order->store->phone ?? 'N/A',
                'order_amount' => $order->order_amount,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'delivery_date' => $order->delivery_date,
                'created_at' => $order->created_at->format('d M Y H:i'),
                'updated_at' => $order->updated_at->format('d M Y H:i'),
                'items_count' => $order->details->count(),
            ];
        });

        // Return JSON for AJAX request
        return response()->json([
            'success' => true,
            'trip' => [
                'id' => $trip->id,
                'trip_number' => $trip->trip_number,
                'status' => $trip->status,
                'delivery_man' => $trip->deliveryMan ? $trip->deliveryMan->f_name . ' ' . $trip->deliveryMan->l_name : 'N/A',
                'created_at' => $trip->created_at->format('d M Y H:i'),
            ],
            'orders' => $history,
            'summary' => [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('order_amount'),
                'delivered' => $orders->where('order_status', 'delivered')->count(),
                'pending' => $orders->where('order_status', 'pending')->count(),
                'processing' => $orders->where('order_status', 'processing')->count(),
                'paid' => $orders->where('payment_status', 'paid')->count(),
                'unpaid' => $orders->where('payment_status', 'unpaid')->count(),
            ]
        ]);
    }
}
