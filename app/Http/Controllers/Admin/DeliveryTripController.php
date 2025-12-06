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
    // Show bulk assign form


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
    public function create(Request $request)
    {
        // Load delivery men
        $deliveryMen = DeliveryMan::where('is_active', 1)->get();

        // Orders assigned to sales person but not yet to delivery man
        $orders = Order::whereNull('delivery_man_id')
            ->where('order_status', 'pending')
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

            /* ✅ By Selected Orders */ elseif ($request->filled('order_ids')) {
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
                'paytm_qr_code' => $paytm_qr_code,   // ⬅ add this

                ...$businessInfo
            ])->setPaper('a4', 'portrait');

            return $pdf->download('invoices-' . now()->format('YmdHis') . '.pdf');
        }


        // ✅ Normal Page Render
        return view('delivery_trips.create', compact('deliveryMen', 'orders', 'trips'));
    }







    // Store bulk assignment
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
        } while (\App\Models\DeliveryTrip::where('trip_number', $tripNumber)->exists());

        // ✅ Update selected orders
        Order::whereIn('id', $request->order_ids)
            ->update([
                'delivery_man_id' => $request->delivery_man_id,
                'trip_number' => $tripNumber,
                'order_status' => 'processing', // You can change to 'on_route' if needed
            ]);

        // ✅ Create delivery trip record
        DeliveryTrip::create([
            'trip_number' => $tripNumber,
            'delivery_man_id' => $request->delivery_man_id,
            'order_ids' => json_encode($request->order_ids),
            'status' => 'pending', // default status
        ]);

        return redirect()->back()->with('success', 'Orders assigned successfully with Trip No: ' . strtoupper($tripNumber));
    }


}
