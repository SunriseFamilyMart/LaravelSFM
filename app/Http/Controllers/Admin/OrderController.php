<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\BusinessSetting;
use App\Model\DeliveryMan;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Models\OrderPayment;


use App\Models\Supplier;
use App\Models\DeliveryChargeByArea;
use App\Models\OfflinePayment;
use App\Models\OrderArea;
use App\Models\OrderPartialPayment;
use App\Traits\HelperTrait;
use App\User;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function App\CentralLogics\translate;
use Illuminate\Support\Str;
use App\Models\DeliveryTrip;

class OrderController extends Controller
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
     * @param Request $request
     * @param $status
     * @return Factory|View|Application
     */
    public function list(Request $request, $status): View|Factory|Application
    {
        $queryParam = [];
        $search = $request['search'];

        $branches = $this->branch->all();
        $branchId = $request['branch_id'];
        $startDate = $request['start_date'];
        $endDate = $request['end_date'];

        $this->order->where(['checked' => 0])->update(['checked' => 1]);

        $query = $this->order->with(['customer', 'branch'])
            ->when((!is_null($branchId) && $branchId != 'all'), function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })->when((!is_null($startDate) && !is_null($endDate)), function ($query) use ($startDate, $endDate) {
                return $query->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            });

        if ($status != 'all') {
            $query->where(['order_status' => $status]);
        }

        $queryParam = ['branch_id' => $branchId, 'start_date' => $startDate, 'end_date' => $endDate];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('payment_status', 'like', "{$value}%");
                }
            });
            $queryParam['search'] = $search;
        }

        $orders = $query->notPos()->orderBy('id', 'desc')->paginate(Helpers::getPagination())->appends($queryParam);

        $countData = [];
        $orderStatuses = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'canceled', 'returned', 'failed'];

        foreach ($orderStatuses as $orderStatus) {
            $countData[$orderStatus] = $this->order->notPos()->where('order_status', $orderStatus)
                ->when(!is_null($branchId) && $branchId != 'all', function ($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->when(!is_null($startDate) && !is_null($endDate), function ($query) use ($startDate, $endDate) {
                    return $query->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $endDate);
                })
                ->count();
        }

        return view('admin-views.order.list', compact('orders', 'status', 'search', 'branches', 'branchId', 'startDate', 'endDate', 'countData'));
    }



public function orderManagement(Request $request)
{
    $supplierId = $request->supplier;
    $productId  = $request->product;
    $status     = $request->status;

    $limit = $request->get('limit', 10);

    $orders = Order::with(['details.product.supplier', 'payments'])
        ->when($status, fn ($q) => $q->where('order_status', $status))
        ->whereHas('details', function ($q) use ($productId, $supplierId) {

            if ($productId) {
                $q->where('product_id', $productId);
            }

            if ($supplierId) {
                $q->whereHas('product', function ($p) use ($supplierId) {
                    $p->where('supplier_id', $supplierId);
                });
            }
        })
        ->latest()
        ->paginate($limit); // ⭐ Pagination added here

    /* ================= SUMMARY ================= */
    // items() required because paginate returns LengthAwarePaginator
    $orderCollection = collect($orders->items());

    $summary = [
        'total_orders' => $orderCollection->count(),
        'delivered'    => $orderCollection->where('order_status', 'delivered')->count(),
        'in_progress'  => $orderCollection->where('order_status', 'processing')->count(),
        'delayed'      => $orderCollection->where('order_status', 'failed')->count(),
        'cancelled'    => $orderCollection->where('order_status', 'canceled')->count(),

        'total_purchase' => $orderCollection->sum('order_amount'),
        'total_paid'     => $orderCollection->sum(fn ($o) => $o->payments->sum('amount')),
        'outstanding'    => $orderCollection->sum('order_amount')
                                - $orderCollection->sum(fn ($o) => $o->payments->sum('amount')),
    ];

    return view('admin-views.order.ordermanagement', [
        'orders'    => $orders,        // paginator object
        'suppliers' => Supplier::all(),
        'products'  => Product::all(),
        'summary'   => $summary
    ]);
}
public function updateOrder(Request $request, $id)
{
    $request->validate([
        'order_status' => 'required',
        'paid_amount'  => 'nullable|numeric|min:0',
    ]);

    $order = Order::with('payments')->findOrFail($id);

    // Update Status
    $order->order_status = $request->order_status;
    $order->save();

    // Add NEW PAYMENT (if provided)
    if ($request->paid_amount > 0) {
        $order->payments()->create([
            'amount' => $request->paid_amount,
            'method' => 'manual'
        ]);
    }

    return back()->with('success', 'Order updated successfully.');
}

public function createOrder()
{
    return view('admin-views.order.create', [
        'suppliers' => Supplier::all(),
        'products'  => Product::select('id','name','price')->get()

    ]);
}

// AJAX: Get products by supplier
public function getSupplierProducts($supplierId)
{
    return Product::where('supplier_id', $supplierId)->get();
}

// AJAX: Get product price
public function getProductPrice($id)
{
    return Product::find($id);
}


public function storeOrder(Request $request)
{
    $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'order_date' => 'required|date',
        'expected_date' => 'required|date',
        'delivery_date' => 'nullable|date',
        'invoice_no' => 'required',
        'order_status' => 'required',
        'payment_mode' => 'required',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.qty' => 'required|numeric|min:1',
        'products.*.price' => 'required|numeric|min:0',
    ]);

    /* ---------------------- CREATE ORDER ---------------------- */
    $order = Order::create([
        'order_amount' => 0,
        'payment_status' => 'unpaid',
        'order_status' => $request->order_status,
        'payment_method' => $request->payment_mode,
        'date' => $request->order_date,
        'delivery_date' => $request->delivery_date,
        'order_note' => $request->comment,
    ]);

    $total = 0;

    foreach ($request->products as $item) {
        $lineTotal = $item['qty'] * $item['price'];
        $total += $lineTotal;

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'quantity' => $item['qty'],
            'price' => $item['price'],
            'tax_amount' => 0,
            'discount_on_product' => 0,
            'discount_type' => 'amount',
            'unit' => 'pc'
        ]);
    }

    /* ---------------------- UPDATE ORDER TOTAL ---------------------- */
    $order->order_amount = $total;
    $order->save();

    /* ---------------------- PAYMENT ---------------------- */
    OrderPayment::create([
        'order_id' => $order->id,
        'payment_method' => $request->payment_mode,
        'amount' => $request->paid ?? 0,
        'payment_date' => now(),
    ]);

    return redirect()->route('admin.orders.ordermanagement')
        ->with('success', 'Order created successfully.');
}


    /**
     * @param $id
     * @return View|Factory|RedirectResponse|Application
     */
    public function details($id): Factory|View|Application|RedirectResponse
    {
        $order = $this->order
            ->with([
                'details',
                'offline_payment',
                'editLogs', // ✅ fetch edit logs
                'editLogs.deliveryMan', // optional to show DM name
                'editLogs.orderDetail'
            ])
            ->where(['id' => $id])
            ->first();

        $deliverymanList = $this->delivery_man->where(['is_active' => 1])
            ->where(function ($query) use ($order) {
                $query->where('branch_id', $order->branch_id)
                    ->orWhere('branch_id', 0);
            })->get();

        if ($order) {
            return view('admin-views.order.order-view', compact('order', 'deliverymanList'));
        } else {
            Toastr::info(translate('No more orders!'));
            return back();
        }
    }


    /**
     * @param $order
     * @param $amount
     * @return void
     */
    private function calculateRefundAmount($order, $amount): void
    {
        $customer = $this->user->find($order['user_id']);
        $wallet = CustomerLogic::create_wallet_transaction($customer->id, $amount, 'refund', $order['id']);
        if ($wallet) {
            $customer->wallet_balance += $amount;
        }
        $customer->save();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): \Illuminate\Http\RedirectResponse
    {
        $order = $this->order->find($request->id);

        if (in_array($order->order_status, ['returned', 'delivered', 'failed', 'canceled'])) {
            Toastr::warning(translate('you_can_not_change_the_status_of ' . $order->order_status . ' order'));
            return back();
        }

        if ($request->order_status == 'delivered' && $order['payment_status'] != 'paid') {
            Toastr::warning(translate('you_can_not_delivered_a_order_when_order_status_is_not_paid. please_update_payment_status_first'));
            return back();
        }

        if ($request->order_status == 'delivered' && $order['transaction_reference'] == null && !in_array($order['payment_method'], ['cash_on_delivery', 'wallet_payment', 'offline_payment'])) {
            Toastr::warning(translate('add_your_payment_reference_first'));
            return back();
        }

        if (($request->order_status == 'out_for_delivery' || $request->order_status == 'delivered') && $order['delivery_man_id'] == null && $order['order_type'] != 'self_pickup') {
            Toastr::warning(translate('Please assign delivery man first!'));
            return back();
        }

        //refund amount to wallet
        if (in_array($request['order_status'], ['returned', 'failed', 'canceled']) && $order['is_guest'] == 0 && isset($order->customer) && Helpers::get_business_settings('wallet_status') == 1) {

            if ($order['payment_method'] == 'wallet_payment' && $order->partial_payment->isEmpty()) {
                $this->calculateRefundAmount(order: $order, amount: $order->order_amount);
            }

            if ($order['payment_method'] != 'cash_on_delivery' && $order['payment_method'] != 'wallet_payment' && $order['payment_method'] != 'offline_payment' && $order->partial_payment->isEmpty()) {
                $this->calculateRefundAmount(order: $order, amount: $order->order_amount);
            }

            if ($order['payment_method'] == 'offline_payment' && $order['payment_status'] == 'paid' && $order->partial_payment->isEmpty()) {
                $this->calculateRefundAmount(order: $order, amount: $order['order_amount']);
            }

            if ($order->partial_payment->isNotEmpty()) {
                $partial_payment_total = $order->partial_payment->sum('paid_amount');
                $this->calculateRefundAmount(order: $order, amount: $partial_payment_total);
            }
        }

        //stock adjust
        if ($request->order_status == 'returned' || $request->order_status == 'failed' || $request->order_status == 'canceled') {
            foreach ($order->details as $detail) {
                if (!isset($detail->variant)) {
                    if ($detail['is_stock_decreased'] == 1) {
                        $product = $this->product->find($detail['product_id']);
                        if (!isset($detail->variant)) {
                            dd('ache');
                        }

                        if ($product != null) {
                            $type = json_decode($detail['variation'])[0]->type;
                            $variationStore = [];
                            foreach (json_decode($product['variations'], true) as $var) {
                                if ($type == $var['type']) {
                                    $var['stock'] += $detail['quantity'];
                                }
                                $variationStore[] = $var;
                            }
                            $this->product->where(['id' => $product['id']])->update([
                                'variations' => json_encode($variationStore),
                                'total_stock' => $product['total_stock'] + $detail['quantity'],
                            ]);
                            $this->order_detail->where(['id' => $detail['id']])->update([
                                'is_stock_decreased' => 0,
                            ]);
                        } else {
                            Toastr::warning(translate('Product_deleted'));
                        }
                    }
                }
            }
        } else {
            foreach ($order->details as $detail) {
                if (!isset($detail->variant)) {
                    if ($detail['is_stock_decreased'] == 0) {

                        $product = $this->product->find($detail['product_id']);
                        if ($product != null) {
                            foreach ($order->details as $c) {
                                $product = $this->product->find($c['product_id']);
                                $type = json_decode($c['variation'])[0]->type;
                                foreach (json_decode($product['variations'], true) as $var) {
                                    if ($type == $var['type'] && $var['stock'] < $c['quantity']) {
                                        Toastr::error(translate('Stock is insufficient!'));
                                        return back();
                                    }
                                }
                            }

                            $type = json_decode($detail['variation'])[0]->type;
                            $variationStore = [];
                            foreach (json_decode($product['variations'], true) as $var) {
                                if ($type == $var['type']) {
                                    $var['stock'] -= $detail['quantity'];
                                }
                                $variationStore[] = $var;
                            }
                            $this->product->where(['id' => $product['id']])->update([
                                'variations' => json_encode($variationStore),
                                'total_stock' => $product['total_stock'] - $detail['quantity'],
                            ]);
                            $this->order_detail->where(['id' => $detail['id']])->update([
                                'is_stock_decreased' => 1,
                            ]);
                        } else {
                            Toastr::warning(translate('Product_deleted'));
                        }

                    }
                }
            }
        }

        if ($request->order_status == 'delivered') {
            if ($order->is_guest == 0) {
                if ($order->user_id) {
                    CustomerLogic::create_loyalty_point_transaction($order->user_id, $order->id, $order->order_amount, 'order_place');
                }

                $user = $this->user->find($order->user_id);
                $isFirstOrder = $this->order->where(['user_id' => $user->id, 'order_status' => 'delivered'])->count('id');
                $referredByUser = $this->user->find($user->referred_by);

                if ($isFirstOrder < 2 && isset($user->referred_by) && isset($referredByUser)) {
                    if ($this->business_setting->where('key', 'ref_earning_status')->first()->value == 1) {
                        CustomerLogic::referral_earning_wallet_transaction($order->user_id, 'referral_order_place', $referredByUser->id);
                    }
                }
            }

            if ($order['payment_method'] == 'cash_on_delivery') {
                $partialData = OrderPartialPayment::where(['order_id' => $order->id])->first();
                if ($partialData) {
                    $partial = new OrderPartialPayment;
                    $partial->order_id = $order['id'];
                    $partial->paid_with = 'cash_on_delivery';
                    $partial->paid_amount = $partialData->due_amount;
                    $partial->due_amount = 0;
                    $partial->save();
                }
            }
        }

        $order->order_status = $request->order_status;
        $order->save();

        $message = Helpers::order_status_update_message($request->order_status);
        $languageCode = $order->is_guest == 0 ? ($order->customer ? $order->customer->language_code : 'en') : ($order->guest ? $order->guest->language_code : 'en');
        $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);

        if ($languageCode != 'en') {
            $message = $this->translate_message($languageCode, $request->order_status);
        }
        $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type' => 'order'
                ];
                Helpers::send_push_notif_to_device($customerFcmToken, $data);
            }
        } catch (\Exception $e) {
            Toastr::warning(\App\CentralLogics\translate('Push notification failed for Customer!'));
        }

        if ($request->order_status == 'processing' && $order->delivery_man != null) {
            $deliverymanFcmToken = $order->delivery_man->fcm_token;
            $message = Helpers::order_status_update_message('deliveryman_order_processing');
            $deliverymanLanguageCode = $order->delivery_man->language_code ?? 'en';

            if ($deliverymanLanguageCode != 'en') {
                $message = $this->translate_message($deliverymanLanguageCode, 'deliveryman_order_processing');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            try {
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order'
                    ];
                    Helpers::send_push_notif_to_device($deliverymanFcmToken, $data);
                }
            } catch (\Exception $e) {
                Toastr::warning(\App\CentralLogics\translate('Push notification failed for DeliveryMan!'));
            }
        }

        Toastr::success(translate('Order status updated!'));
        return back();
    }

    /**
     * @param $order_id
     * @param $delivery_man_id
     * @return JsonResponse
     */
    public function addDeliveryman($order_id, $delivery_man_id): \Illuminate\Http\JsonResponse
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }

        $order = $this->order->find($order_id);

        // Only allow change if order is not delivered, returned, failed, or canceled
        if (in_array($order->order_status, ['pending', 'confirmed', 'delivered', 'returned', 'failed', 'canceled'])) {
            return response()->json(['status' => false, 'message' => 'Cannot change delivery man for this order'], 200);
        }

        $oldDeliveryManId = $order->delivery_man_id;
        $order->delivery_man_id = $delivery_man_id;
        $order->save();

        // Update delivery trip
        $trip = DeliveryTrip::where('trip_number', $order->trip_number)->first();
        if ($trip) {
            $orderIds = collect($trip->order_ids);

            // Remove order from old delivery man's trip if different
            if ($oldDeliveryManId && $oldDeliveryManId != $delivery_man_id) {
                $trip->order_ids = $orderIds->filter(fn($id) => $id != $order->id)->values();
                $trip->save();
            }

            // Check if new delivery man already has a trip for today
            $newTrip = DeliveryTrip::where('delivery_man_id', $delivery_man_id)
                ->whereDate('created_at', now()->toDateString())
                ->first();

            if ($newTrip) {
                $newTrip->order_ids = array_merge($newTrip->order_ids ?? [], [$order->id]);
                $order->trip_number = $newTrip->trip_number;
                $order->save();
                $newTrip->save();
            } else {
                // Create new trip for new delivery man
                $newTripNumber = 'TRIP-' . strtoupper(Str::random(6));
                DeliveryTrip::create([
                    'trip_number' => $newTripNumber,
                    'delivery_man_id' => $delivery_man_id,
                    'order_ids' => [$order->id],
                ]);
                $order->trip_number = $newTripNumber;
                $order->save();
            }
        }

        Toastr::success('Deliveryman successfully assigned/changed!');
        return response()->json(['status' => true], 200);
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function paymentStatus(Request $request): \Illuminate\Http\RedirectResponse
    {
        $order = $this->order->find($request->id);

        if ($order->payment_method == 'offline_payment' && isset($order->offline_payment) && $order->offline_payment?->status != 1) {
            Toastr::warning(translate('please_verify_your_offline_payment_verification'));
            return back();
        }

        if ($request->payment_status == 'paid' && $order['transaction_reference'] == null && $order['payment_method'] != 'cash_on_delivery') {
            Toastr::warning(translate('Add your payment reference code first!'));
            return back();
        }

        if ($request->payment_status == 'paid' && $order['order_status'] == 'pending') {
            $order->order_status = 'confirmed';

            $message = Helpers::order_status_update_message('confirmed');
            $languageCode = $order->is_guest == 0 ? ($order->customer ? $order->customer->language_code : 'en') : ($order->guest ? $order->guest->language_code : 'en');
            $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);

            if ($languageCode != 'en') {
                $message = $this->translate_message($languageCode, 'confirmed');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            try {
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order'
                    ];
                    Helpers::send_push_notif_to_device($customerFcmToken, $data);
                }
            } catch (\Exception $e) {
                //
            }

        }
        $order->payment_status = $request->payment_status;
        $order->save();
        Toastr::success(translate('Payment status updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function updateShipping(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required',
        ]);

        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'road' => $request->road,
            'house' => $request->house,
            'floor' => $request->floor,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('customer_addresses')->where('id', $id)->update($address);
        Toastr::success(translate('Delivery Information updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function updateTimeSlot(Request $request)
    {
        if ($request->ajax()) {
            $order = $this->order->find($request->id);
            $order->time_slot_id = $request->timeSlot;
            $order->save();
            $data = $request->timeSlot;

            return response()->json($data);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function updateDeliveryDate(Request $request)
    {
        if ($request->ajax()) {
            $order = $this->order->find($request->id);
            $order->delivery_date = $request->deliveryDate;
            $order->save();
            $data = $request->deliveryDate;
            return response()->json($data);
        }
    }

    /**
     * @param $id
     * @return Factory|View|Application
     */
    public function generateInvoice($id): View|Factory|Application
    {
        $order = $this->order->where('id', $id)->first();
        $footer_text = $this->business_setting->where(['key' => 'footer_text'])->first();
        return view('admin-views.order.invoice', compact('order', 'footer_text'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function addPaymentReferenceCode(Request $request, $id): RedirectResponse
    {
        $this->order->where(['id' => $id])->update([
            'transaction_reference' => $request['transaction_reference'],
        ]);

        Toastr::success(translate('Payment reference code is added!'));
        return back();
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function branchFilter($id): RedirectResponse
    {
        session()->put('branch_filter', $id);
        return back();
    }

    /**
     * @param Request $request
     * @param $status
     * @return string|StreamedResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportOrders(Request $request, $status): StreamedResponse|string
    {
        $queryParam = [];
        $search = $request['search'];
        $branchId = $request['branch_id'];
        $startDate = $request['start_date'];
        $endDate = $request['end_date'];

        $query = $this->order->with(['customer', 'branch'])
            ->when((!is_null($branchId) && $branchId != 'all'), function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })->when((!is_null($startDate) && !is_null($endDate)), function ($query) use ($startDate, $endDate) {
                return $query->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            });

        if ($status != 'all') {
            $query->where(['order_status' => $status]);
        }

        $queryParam = ['branch_id' => $branchId, 'start_date' => $startDate, 'end_date' => $endDate];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('payment_status', 'like', "{$value}%");
                }
            });
            $queryParam['search'] = $search;
        }

        $orders = $query->notPos()->orderBy('id', 'desc')->get();

        $storage = [];
        foreach ($orders as $order) {
            $branch = $order->branch ? $order->branch->name : '';
            $customer = $order->customer ? $order->customer->f_name . ' ' . $order->customer->l_name : 'Customer Deleted';
            $deliveryman = $order->delivery_man ? $order->delivery_man->f_name . ' ' . $order->delivery_man->l_name : '';
            $timeslot = $order->time_slot ? $order->time_slot->start_time . ' - ' . $order->time_slot->end_time : '';

            $storage[] = [
                'order_id' => $order['id'],
                'customer' => $customer,
                'order_amount' => $order['order_amount'],
                'coupon_discount_amount' => $order['coupon_discount_amount'],
                'payment_status' => $order['payment_status'],
                'order_status' => $order['order_status'],
                'total_tax_amount' => $order['total_tax_amount'],
                'payment_method' => $order['payment_method'],
                'transaction_reference' => $order['transaction_reference'],
                'delivery_man' => $deliveryman,
                'delivery_charge' => $order['delivery_charge'],
                'coupon_code' => $order['coupon_code'],
                'order_type' => $order['order_type'],
                'branch' => $branch,
                'time_slot_id' => $timeslot,
                'date' => $order['date'],
                'delivery_date' => $order['delivery_date'],
                'extra_discount' => $order['extra_discount'],
            ];
        }
        return (new FastExcel($storage))->download('orders.xlsx');
    }

    /**
     * @param $order_id
     * @param $status
     * @return JsonResponse
     */
    public function verifyOfflinePayment($order_id, $status): JsonResponse
    {
        $offlineData = OfflinePayment::where(['order_id' => $order_id])->first();

        if (!isset($offlineData)) {
            return response()->json(['status' => false, 'message' => translate('offline data not found')], 200);
        }

        $order = Order::find($order_id);
        if (!isset($order)) {
            return response()->json(['status' => false, 'message' => translate('order not found')], 200);
        }

        if ($status == 1) {
            if ($order->order_status == 'canceled') {
                return response()->json(['status' => false, 'message' => translate('Canceled order can not be verified')], 200);
            }

            $offlineData->status = $status;
            $offlineData->save();

            $order->order_status = 'confirmed';
            $order->payment_status = 'paid';
            $order->save();

            $message = Helpers::order_status_update_message('confirmed');
            $languageCode = $order->is_guest == 0 ? ($order->customer ? $order->customer->language_code : 'en') : ($order->guest ? $order->guest->language_code : 'en');
            $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);

            if ($languageCode != 'en') {
                $message = $this->translate_message($languageCode, 'confirmed');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            try {
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order'
                    ];
                    Helpers::send_push_notif_to_device($customerFcmToken, $data);
                }
            } catch (\Exception $e) {
                //
            }

        } elseif ($status == 2) {
            $offlineData->status = $status;
            $offlineData->save();

            $customerFcmToken = null;
            if ($order->is_guest == 0) {
                $customerFcmToken = $order->customer ? $order->customer->cm_firebase_token : null;
            } elseif ($order->is_guest == 1) {
                $customerFcmToken = $order->guest ? $order->guest->fcm_token : null;
            }
            if ($customerFcmToken != null) {
                try {
                    $data = [
                        'title' => translate('Order'),
                        'description' => translate('Offline payment is not verified'),
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'order',
                    ];
                    Helpers::send_push_notif_to_device($customerFcmToken, $data);
                } catch (\Exception $e) {
                }
            }
        }
        return response()->json(['status' => true, 'message' => translate("offline payment verify status changed")], 200);
    }

    public function updateOrderDeliveryArea(Request $request, $order_id)
    {
        $request->validate([
            'selected_area_id' => 'required'
        ]);

        $order = $this->order->find($order_id);
        if (!$order) {
            Toastr::warning(translate('order not found'));
            return back();
        }

        if ($order->order_status == 'delivered') {
            Toastr::warning(translate('you_can_not_change_the_area_once_the_order_status_is_delivered'));
            return back();
        }

        $branch = Branch::with(['delivery_charge_setup', 'delivery_charge_by_area'])
            ->where(['id' => $order['branch_id']])
            ->first(['id', 'name', 'status']);

        if ($branch->delivery_charge_setup->delivery_charge_type != 'area') {
            Toastr::warning(translate('this branch selected delivery type is not area'));
            return back();
        }

        $area = DeliveryChargeByArea::where(['id' => $request['selected_area_id'], 'branch_id' => $order->branch_id])->first();
        if (!$area) {
            Toastr::warning(translate('Area not found'));
            return back();
        }

        $order->delivery_charge = $area->delivery_charge;
        $order->save();

        $orderArea = $this->orderArea->firstOrNew(['order_id' => $order_id]);
        $orderArea->area_id = $request->selected_area_id;
        $orderArea->save();

        $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);
        try {
            if ($customerFcmToken != null) {
                $data = [
                    'title' => translate('Order'),
                    'description' => translate('order delivery area updated'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order'
                ];
                Helpers::send_push_notif_to_device($customerFcmToken, $data);
            }
        } catch (\Exception $e) {
            //
        }

        Toastr::success(translate('Order delivery area updated successfully.'));
        return back();
    }
}
