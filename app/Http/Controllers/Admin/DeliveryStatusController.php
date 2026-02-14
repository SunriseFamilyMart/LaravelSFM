<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\DeliveryMan;
use App\Model\Order;
use App\Models\Store;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryStatusController extends Controller
{
    /**
     * Display delivery status page with filters
     * 
     * @param Request $request
     * @return View|Factory|Application
     */
    public function index(Request $request): View|Factory|Application
    {
        $branchId = $request->branch_id ?? 'all';
        $deliveryManId = $request->delivery_man_id ?? 'all';
        $startDate = $request->start_date ?? null;
        $endDate = $request->end_date ?? null;
        $route = $request->route ?? 'all';
        $paymentStatus = $request->payment_status ?? 'all';
        $collectionStatus = $request->collection_status ?? 'all';
        $search = $request->search ?? null;

        // Base query for summary totals
        $summaryQuery = Order::where('order_status', 'delivered')
            ->when($branchId && $branchId != 'all', fn($q) => $q->where('branch_id', $branchId))
            ->when($deliveryManId && $deliveryManId != 'all', fn($q) => $q->where('delivery_man_id', $deliveryManId))
            ->when($startDate && $endDate, fn($q) => $q->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate))
            ->when($route && $route != 'all', fn($q) => $q->whereHas('store', fn($s) => $s->where('route_name', $route)))
            ->when($paymentStatus && $paymentStatus != 'all', fn($q) => $q->where('payment_status', $paymentStatus))
            ->when($collectionStatus && $collectionStatus != 'all', function($q) use($collectionStatus) {
                if($collectionStatus == 'collected') {
                    return $q->where('is_collected', true);
                } else {
                    return $q->where('is_collected', false);
                }
            })
            ->when($search, function($q) use($search) {
                $keys = explode(' ', $search);
                return $q->where(function($subQ) use($keys) {
                    foreach($keys as $key) {
                        $subQ->orWhere('orders.id', 'like', "%{$key}%")
                             ->orWhereHas('customer', fn($c) => $c->where('f_name', 'like', "%{$key}%")->orWhere('l_name', 'like', "%{$key}%")->orWhere('phone', 'like', "%{$key}%"));
                    }
                });
            });

        // Calculate totals
        $totals = [
            'total_orders' => (clone $summaryQuery)->count(),
            'total_amount' => (clone $summaryQuery)->sum('order_amount') ?? 0,
            'total_paid' => (clone $summaryQuery)->sum('paid_amount') ?? 0,
        ];

        // UPI Total from payment_ledgers
        $orderIds = (clone $summaryQuery)->pluck('id')->toArray();
        $totals['total_upi_paid'] = DB::table('payment_ledgers')
            ->whereIn('order_id', $orderIds)
            ->where('payment_method', 'upi')
            ->sum('amount') ?? 0;

        // Orders query for table
        $orders = Order::where('order_status', 'delivered')
            ->with(['customer', 'delivery_man', 'time_slot', 'branch', 'store'])
            ->when($branchId && $branchId != 'all', fn($q) => $q->where('orders.branch_id', $branchId))
            ->when($deliveryManId && $deliveryManId != 'all', fn($q) => $q->where('orders.delivery_man_id', $deliveryManId))
            ->when($startDate && $endDate, fn($q) => $q->whereDate('orders.created_at', '>=', $startDate)->whereDate('orders.created_at', '<=', $endDate))
            ->when($route && $route != 'all', fn($q) => $q->whereHas('store', fn($s) => $s->where('route_name', $route)))
            ->when($paymentStatus && $paymentStatus != 'all', fn($q) => $q->where('orders.payment_status', $paymentStatus))
            ->when($collectionStatus && $collectionStatus != 'all', function($q) use($collectionStatus) {
                if($collectionStatus == 'collected') {
                    return $q->where('orders.is_collected', true);
                } else {
                    return $q->where('orders.is_collected', false);
                }
            })
            ->when($search, function($q) use($search) {
                $keys = explode(' ', $search);
                return $q->where(function($subQ) use($keys) {
                    foreach($keys as $key) {
                        $subQ->orWhere('orders.id', 'like', "%{$key}%")
                             ->orWhereHas('customer', fn($c) => $c->where('f_name', 'like', "%{$key}%")->orWhere('l_name', 'like', "%{$key}%")->orWhere('phone', 'like', "%{$key}%"));
                    }
                });
            })
            ->orderBy('orders.delivery_date', 'desc')
            ->paginate(15);

        // Get UPI payments for each order
        foreach ($orders as $order) {
            $upiPayment = DB::table('payment_ledgers')
                ->where('order_id', $order->id)
                ->where('payment_method', 'upi')
                ->first();
            
            $order->upi_amount = $upiPayment->amount ?? 0;
            $order->upi_transaction_id = $upiPayment->transaction_ref ?? null;
        }

        $branches = Branch::all();
        $deliveryMen = DeliveryMan::all();
        $routes = Store::pluck('route_name')->unique()->filter();

        return view('admin-views.delivery-status.index', compact(
            'orders', 
            'totals', 
            'branches', 
            'deliveryMen', 
            'routes', 
            'branchId', 
            'deliveryManId', 
            'startDate', 
            'endDate', 
            'route', 
            'paymentStatus', 
            'collectionStatus', 
            'search'
        ));
    }

    /**
     * Mark order as collected
     * 
     * @param int $orderId
     * @return JsonResponse
     */
    public function markAsCollected($orderId): JsonResponse
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if ($order->is_collected) {
            return response()->json(['success' => false, 'message' => 'Order already marked as collected'], 400);
        }

        $order->is_collected = true;
        $order->save();

        return response()->json([
            'success' => true, 
            'message' => 'Order marked as collected successfully',
            'is_collected' => $order->is_collected
        ]);
    }
}
