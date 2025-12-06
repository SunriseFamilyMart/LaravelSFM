<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryTrip;
use App\Models\Order;
use App\Model\OrderDetail;

class DeliveryDetailsController extends Controller
{
    // Show all trips with related order data
    public function index()
    {
        // dd('zsdfsdf');
        $trips = DeliveryTrip::with('deliveryMan')->latest()->get();
        // dd($trips);
        return view('admin-views.delivery-details.index', compact('trips'));
    }

    // Filter by status (pending, completed, on_route)
    public function status($status)
    {
        $trips = DeliveryTrip::where('status', $status)->with('deliveryMan')->get();
        return view('admin-views.delivery-details.index', compact('trips', 'status'));
    }

    // Show a single delivery trip details
    public function view($id)
    {
        $trip = DeliveryTrip::findOrFail($id);

        // Extract order IDs from JSON field
        // $orderIds = json_decode($trip->order_ids, true);

        $orderIds = is_array($trip->order_ids) ? $trip->order_ids : json_decode($trip->order_ids, true);

        // Fetch related orders and details
        $orders = Order::whereIn('id', $orderIds)->get();
        $orderDetails = OrderDetail::whereIn('order_id', $orderIds)->get();

        return view('admin-views.delivery-details.view', compact('trip', 'orders', 'orderDetails'));
    }
}
