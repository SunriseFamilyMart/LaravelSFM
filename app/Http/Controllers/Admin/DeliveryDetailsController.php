<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryTrip;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Models\Store;
use App\Models\PaymentLedger;
use App\Model\Branch;
use App\Services\StorePaymentFifoService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use function App\CentralLogics\translate;

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

    /**
     * Payment Collection page
     * Shows stores with outstanding balances for payment collection
     */
    public function paymentCollection(Request $request)
    {
        $branches = Branch::all();
        $stores = Store::all();

        $branchId = $request->input('branch_id', 'all');
        $storeId = $request->input('store_id', 'all');
        $paymentStatus = $request->input('payment_status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Get stores with their outstanding balances
        $query = Store::select('stores.*')
            ->selectRaw('COALESCE(SUM(orders.order_amount + orders.total_tax_amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(orders.paid_amount), 0) as paid_amount')
            ->selectRaw('COALESCE(SUM(orders.order_amount + orders.total_tax_amount), 0) - COALESCE(SUM(orders.paid_amount), 0) as due_amount')
            ->selectRaw('MAX(payment_ledgers.created_at) as last_payment_date')
            ->selectRaw('COUNT(orders.id) as total_orders')
            ->leftJoin('orders', 'stores.id', '=', 'orders.store_id')
            ->leftJoin('payment_ledgers', function($join) {
                $join->on('stores.id', '=', 'payment_ledgers.store_id')
                     ->where('payment_ledgers.entry_type', '=', 'CREDIT');
            })
            ->groupBy('stores.id', 'stores.name', 'stores.phone', 'stores.address', 'stores.created_at', 'stores.updated_at');

        // Apply filters
        if ($branchId && $branchId != 'all') {
            $query->whereHas('orders', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        if ($storeId && $storeId != 'all') {
            $query->where('stores.id', $storeId);
        }

        if ($startDate && $endDate) {
            $query->whereHas('orders', function($q) use ($startDate, $endDate) {
                $q->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
            });
        }

        $storeBalances = $query->get();

        // Filter by payment status
        if ($paymentStatus && $paymentStatus != 'all') {
            $storeBalances = $storeBalances->filter(function($store) use ($paymentStatus) {
                $dueAmount = $store->due_amount;
                if ($paymentStatus == 'paid') {
                    return $dueAmount <= 0;
                } elseif ($paymentStatus == 'partial') {
                    return $dueAmount > 0 && $store->paid_amount > 0;
                } elseif ($paymentStatus == 'unpaid') {
                    return $store->paid_amount == 0;
                }
                return true;
            });
        }

        // Calculate branch-level totals
        $branchTotals = [
            'total_orders' => $storeBalances->sum('total_orders'),
            'total_amount' => $storeBalances->sum('total_amount'),
            'paid_amount' => $storeBalances->sum('paid_amount'),
            'due_amount' => $storeBalances->sum('due_amount'),
        ];

        return view('admin-views.delivery-details.payment-collection', compact(
            'storeBalances', 'branches', 'stores', 'branchId', 'storeId', 
            'paymentStatus', 'startDate', 'endDate', 'branchTotals'
        ));
    }

    /**
     * Record a payment from a store
     */
    public function recordPayment(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,upi,bank,cheque,other',
            'transaction_ref' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Use the FIFO service to process the payment
            StorePaymentFifoService::apply(
                storeId: $request->store_id,
                amount: $request->amount,
                method: $request->payment_method,
                txnId: $request->transaction_ref,
                userId: auth()->id()
            );

            DB::commit();

            Toastr::success(translate('Payment recorded successfully'));
            return redirect()->back();

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to record payment: ') . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * UPI Verification page
     * Shows all UPI transactions pending verification
     */
    public function upiVerification(Request $request)
    {
        $status = $request->input('status', 'pending');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $deliveryManId = $request->input('delivery_man_id', 'all');

        // Get UPI transactions from payment_ledgers
        $query = PaymentLedger::with(['store', 'order'])
            ->where('payment_method', 'upi')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
        }

        // Note: We don't have a direct delivery_man relationship on PaymentLedger,
        // so we'll need to join through orders if needed
        if ($deliveryManId && $deliveryManId != 'all') {
            $query->whereHas('order', function($q) use ($deliveryManId) {
                $q->where('delivery_man_id', $deliveryManId);
            });
        }

        $upiTransactions = $query->paginate(20);

        // Get delivery men for filter
        $deliveryMen = \App\Model\DeliveryMan::all();

        return view('admin-views.delivery-details.upi-verification', compact(
            'upiTransactions', 'status', 'startDate', 'endDate', 
            'deliveryManId', 'deliveryMen'
        ));
    }

    /**
     * Update UPI transaction status
     */
    public function updateUpiStatus(Request $request)
    {
        $request->validate([
            'ledger_id' => 'required|exists:payment_ledgers,id',
            'action' => 'required|in:verify,reject',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $ledger = PaymentLedger::findOrFail($request->ledger_id);

            // Update remarks to indicate verification status
            $action = $request->action == 'verify' ? 'verified' : 'rejected';
            $remarks = $ledger->remarks ?? '';
            $remarks .= " | {$action} by " . auth()->user()->f_name . " on " . now()->toDateTimeString();
            if ($request->remarks) {
                $remarks .= " | Note: " . $request->remarks;
            }

            $ledger->remarks = $remarks;
            $ledger->save();

            $message = $request->action == 'verify' 
                ? translate('UPI transaction verified successfully')
                : translate('UPI transaction rejected successfully');
            
            Toastr::success($message);
            return redirect()->back();

        } catch (\Exception $e) {
            Toastr::error(translate('Failed to update UPI status: ') . $e->getMessage());
            return redirect()->back();
        }
    }
}
