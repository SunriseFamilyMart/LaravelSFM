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
use App\Services\PaymentFifoService;
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
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\OrderChangeLog;
use App\Models\InventoryTransaction;
use App\Models\GstLedger;
use App\Models\AuditLog;


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
    $search = $request['search'] ?? null;

    $branches = $this->branch->all();
    $branchId = $request['branch_id'] ?? 'all';
    $deliveryManId = $request['delivery_man_id'] ?? 'all';
  $deliveryMen = $this->delivery_man->all();

    $paymentMethod = $request['payment_method'] ?? 'all';
    $startDate = $request['start_date'] ?? null;
    $endDate = $request['end_date'] ?? null;

    // Mark unchecked orders as checked
    $this->order->where(['checked' => 0])->update(['checked' => 1]);

    $query = $this->order->with(['customer', 'branch', 'delivery_man', 'payments'])

        // Branch filter
        ->when(($branchId && $branchId != 'all'), function ($query) use ($branchId) {
            return $query->where('branch_id', $branchId);
        })

        // Date range filter
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            return $query->whereDate('created_at', '>=', $startDate)
                         ->whereDate('created_at', '<=', $endDate);
        })

        // Delivery Man filter
        ->when($deliveryManId && $deliveryManId != 'all', function ($query) use ($deliveryManId) {
            return $query->where('delivery_man_id', $deliveryManId);
        })

        // Payment Method filter
        ->when($paymentMethod && $paymentMethod != 'all', function ($query) use ($paymentMethod) {
            return $query->whereHas('payments', function ($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            });
        });

    // Order status filter
    if ($status != 'all') {
        $query->where(['order_status' => $status]);
    }

    // Search filter
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

    // Prepare query params for pagination links
    $queryParam = array_merge($queryParam, [
        'branch_id' => $branchId,
        'delivery_man_id' => $deliveryManId,
        'payment_method' => $paymentMethod,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $orders = $query->notPos()
                    ->orderBy('id', 'desc')
                    ->paginate(Helpers::getPagination())
                    ->appends($queryParam);

    // Count orders by status (keeping old functionality)
    $countData = [];
    $orderStatuses = ['pending', 'confirmed', 'picking', 'processing', 'out_for_delivery', 'delivered', 'canceled', 'returned', 'failed'];

    foreach ($orderStatuses as $orderStatus) {
        $countData[$orderStatus] = $this->order->notPos()
            ->where('order_status', $orderStatus)
            ->when(!is_null($branchId) && $branchId != 'all', function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when(!is_null($startDate) && !is_null($endDate), function ($query) use ($startDate, $endDate) {
                return $query->whereDate('created_at', '>=', $startDate)
                             ->whereDate('created_at', '<=', $endDate);
            })
            ->count();
    }

    return view('admin-views.order.list', compact(
        'orders', 'status', 'search', 'branches', 'branchId', 'deliveryManId',
        'paymentMethod', 'startDate', 'endDate', 'countData','deliveryMen'
    ));
}


public function orderManagement(Request $request)
{
    $supplierId = $request->supplier;
    $productId  = $request->product;
    $status     = $request->status;

    $limit = $request->get('limit', 10);

    // Fetch orders with related details + only completed payments
    $orders = Order::with([
            'details.product.supplier',
            'payments' => function ($q) {
                $q->where('payment_status', 'complete'); // ONLY completed payments
            }
        ])
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
        ->paginate($limit);

    /* ================= SUMMARY ================= */
    $orderCollection = collect($orders->items());

    // Calculate only COMPLETE payment amounts
    $totalPaid = $orderCollection->sum(function ($order) {
        return $order->payments->where('payment_status', 'complete')->sum('amount');
    });

    $totalPurchase = $orderCollection->sum('order_amount');

    $summary = [
        'total_orders'   => $orderCollection->count(),
        'delivered'      => $orderCollection->where('order_status', 'delivered')->count(),
        'in_progress'    => $orderCollection->where('order_status', 'processing')->count(),
        'delayed'        => $orderCollection->where('order_status', 'failed')->count(),
        'cancelled'      => $orderCollection->where('order_status', 'canceled')->count(),

        'total_purchase' => $totalPurchase,
        'total_paid'     => $totalPaid,
        'outstanding'    => $totalPurchase - $totalPaid,
    ];

    /* Add first detail's invoice & dates */
    foreach ($orders as $order) {
        $order->first_invoice_number = $order->details->first()?->invoice_number ?? null;
        $order->first_expected_date = $order->details->first()?->expected_date ?? null;
        $order->first_order_user = $order->details->first()?->order_user ?? null;
    }

    return view('admin-views.order.ordermanagement', [
        'orders'    => $orders,
        'suppliers' => Supplier::all(),
        'products'  => Product::all(),
        'summary'   => $summary
    ]);
}
public function updateOrder(Request $request, $id)
{
    $request->validate([
        'order_status'    => 'required',
        'paid_amount'     => 'nullable|numeric|min:0',
        'invoice_number'  => 'nullable|string',
        'expected_date'   => 'nullable|date',
        'payment_method'  => 'nullable|string',
        'payment_status'  => 'nullable|string|in:pending,complete,failed',
    ]);

    $order = Order::with('payments', 'orderDetails')->findOrFail($id);

    $orderTotal = (float) $order->order_amount;

    $alreadyPaid = OrderPayment::where('order_id', $order->id)
        ->where('payment_status', 'complete')
        ->sum('amount');

    if ($request->paid_amount > 0) {
        $remainingDue = $orderTotal - $alreadyPaid;

        if ($request->paid_amount > $remainingDue) {
            return back()->withErrors([
                'paid_amount' => "You cannot pay more than remaining due: ₹"
                    . number_format($remainingDue, 2)
            ]);
        }
    }

    // Update Order Status
    $order->order_status = $request->order_status;
    $order->save();

    // Update order_details
    foreach ($order->orderDetails as $detail) {
        if ($request->filled('invoice_number')) {
            $detail->invoice_number = $request->invoice_number;
        }
        if ($request->filled('expected_date')) {
            $detail->expected_date = $request->expected_date;
        }
        $detail->save();
    }

    // Add a payment if entered
    if ($request->paid_amount > 0) {
       /* $order->payments()->create([
            'amount'          => $request->paid_amount,
            'payment_method'  => $request->payment_method ?? 'manual',
            'payment_date'    => now(),
            'payment_status'  => $request->payment_status ?? 'pending',
        ]);
        */

         PaymentFifoService::applyPaymentFIFO(
        storeId: $order->store_id,
        amount: $request->paid_amount,
        paymentMethod: $request->payment_method ?? 'manual',
        txnRef: null,
        userId: auth()->id(),
        branchId: $order->branch_id
    );
    }

    // Recalculate total paid
    $totalPaid = OrderPayment::where('order_id', $order->id)
        ->where('payment_status', 'complete')
        ->sum('amount');

    $order->payment_status = $totalPaid >= $orderTotal ? 'Paid' : 'Unpaid';
    $order->save();

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
        'expected_date' => 'nullable|date',
        'delivery_date' => 'nullable|date',
        'invoice_no' => 'nullable',
        'order_status' => 'required',
        'payment_mode' => 'required',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.qty' => 'required|numeric|min:1',
        'products.*.price' => 'required|numeric|min:0',
        'order_user' => 'nullable|string|max:255',
    ]);

    /** CREATE ORDER */
    $order = Order::create([
        'order_amount' => 0,
        'payment_status' => 'unpaid',
        'order_status' => $request->order_status,
        'payment_method' => $request->payment_mode,
        'date' => $request->order_date,
        'expected_date' => $request->expected_date,
        'delivery_date' => $request->delivery_date,
        'invoice_number' => $request->invoice_no,
        'order_note' => $request->comment,
    ]);

    $total = 0;

    /** ORDER DETAILS */
    foreach ($request->products as $item) {
        $product = Product::find($item['product_id']);
        $lineTotal = $item['price'] * $item['qty'];
        $total += $lineTotal;

        $productDetails = [
            "id" => $product->id,
            "name" => $product->name,
            "description" => $product->description,
            "image" => $product->image,
            "price" => $product->price,
            "variations" => $product->variations,
            "tax" => $product->tax,
            "status" => $product->status,
            "attributes" => $product->attributes,
            "category_ids" => $product->category_ids,
            "choice_options" => $product->choice_options,
            "discount" => $product->discount,
            "discount_type" => $product->discount_type,
            "tax_type" => $product->tax_type,
            "unit" => $product->unit,
            "total_stock" => $product->total_stock,
            "capacity" => $product->capacity,
            "weight" => $product->weight,
        ];

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price' => $item['price'],
            'quantity' => $item['qty'],
            'tax_amount' => 0,
            'discount_on_product' => 0,
            'discount_type' => 'amount',
            'unit' => $product->unit,
            'delivery_date' => $request->delivery_date,
            'expected_date' => $request->expected_date,
            'invoice_number' => $request->invoice_no,
            'vat_status' => "excluded",
            'variation' => json_encode([]),
            'product_details' => $productDetails,
            'order_user' => $request->order_user,
        ]);
    }

    /** UPDATE ORDER TOTAL */
    $order->order_amount = $total;
    $order->save();

    /** ---------------- PAYMENT LOGIC ---------------- */
    $paid = $request->paid ?? 0;

    // Prevent overpayment
    $totalPaidSoFar = OrderPayment::where('order_id', $order->id)->sum('amount');
    if (($totalPaidSoFar + $paid) > $total) {
        return back()->withErrors([
            'paid' => "Total paid amount cannot exceed order total (₹" . number_format($total, 2) . ")"
        ])->withInput();
    }

    // Payment status complete if paid > 0
    $paymentStatus = ($paid > 0) ? 'complete' : 'incomplete';

    /** STORE PAYMENT */
    OrderPayment::create([
        'order_id' => $order->id,
        'payment_method' => $request->payment_mode,
        'amount' => $paid, // Only this transaction amount
        'first_payment' => $paid, // optional, can remove if you don't need
        'second_payment' => 0,    // optional
        'first_payment_date' => now(),
        'second_payment_date' => null,
        'payment_status' => $paymentStatus,
        'payment_date' => now(),
    ]);

    /** UPDATE ORDER PAYMENT STATUS */
    $totalPaid = OrderPayment::where('order_id', $order->id)->sum('amount');
    $order->payment_status = ($totalPaid >= $total) ? 'complete' : 'partial';
    $order->save();

    return redirect()->route('admin.orders.ordermanagement')
        ->with('success', 'Order created successfully.');
}


    /**
     * @param $id
     * @return View|Factory|RedirectResponse|Application
     */
   public function details($id)
{
    $order = $this->order
        ->with([
            'details',
            'offline_payment',
            'editLogs',
            'editLogs.deliveryMan',
            'editLogs.orderDetail',
            'creditNotes',          // ✅ THIS IS THE FIX
            'creditNotes.items', 
        ])
        ->find($id);

    if (!$order) {
        Toastr::info(translate('Order not found!'));
        return back();
    }

    $deliverymanList = $this->delivery_man
        ->where('is_active', 1)
        ->where(function ($query) use ($order) {
            $query->where('branch_id', $order->branch_id)
                  ->orWhere('branch_id', 0);
        })
        ->get();

    return view('admin-views.order.order-view', compact('order', 'deliverymanList'));
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

        if ($status == 'returned') {
    $query->whereIn('order_status', ['returned', 'partial_delivered']);
} elseif ($status != 'all') {
    $query->where('order_status', $status);
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


   public function createCreditNote(Request $request)
{
    DB::beginTransaction();

    try {
        $detail = OrderDetail::lockForUpdate()->findOrFail($request->order_detail_id);
        $order  = Order::findOrFail($detail->order_id);

        $qtyToReturn = (int) $request->qty;
        $price       = (float) $detail->price;
        $gstPercent  = (float) ($detail->tax ?? 0);

        // 🔒 Prevent over-return
        $alreadyReturned = OrderChangeLog::where('order_detail_id', $detail->id)
            ->whereNotNull('credit_note_id')
            ->sum('returned_qty');

        if ($qtyToReturn > ($detail->quantity - $alreadyReturned)) {
            throw new \Exception('Return qty exceeds available quantity');
        }

        $taxable   = $price * $qtyToReturn;
        $gstAmount = ($taxable * $gstPercent) / 100;
        $branch    = (string) $order->branch_id;

        /* ===========================
           1️⃣ CREDIT NOTE
        =========================== */

        $creditNo = 'CN-' . now()->format('Ymd') . '-' . rand(100,999);

        $credit = CreditNote::create([
            'credit_note_no' => $creditNo,
            'order_id'       => $order->id,
            'branch'         => $branch,
            'customer_id'    => $order->user_id,
            'taxable_amount' => $taxable,
            'gst_amount'     => $gstAmount,
            'total_amount'   => $taxable + $gstAmount,
            'reason'         => $request->reason,
        ]);

        CreditNoteItem::create([
            'credit_note_id' => $credit->id,
            'product_id'     => $detail->product_id,
            'quantity'       => $qtyToReturn,
            'price'          => $price,
            'gst_percent'    => $gstPercent,
        ]);

        /* ===========================
           2️⃣ INVENTORY FIFO (BATCH SAFE)
        =========================== */

        $remainingQty = $qtyToReturn;

        $outRows = InventoryTransaction::where('order_id', $order->id)
            ->where('product_id', $detail->product_id)
            ->where('type', 'OUT')
            ->where('remaining_qty', '>', 0)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($outRows as $out) {
            if ($remainingQty <= 0) break;

            $consumeQty = min($remainingQty, $out->remaining_qty);

            // reduce sold quantity
            $out->remaining_qty -= $consumeQty;
            $out->save();

            // reverse inventory with SAME batch
            InventoryTransaction::create([
                'product_id'     => $detail->product_id,
                'branch'         => $branch,
                'batch_id'       => $out->batch_id,
                'type'           => $request->reason === 'restock' ? 'IN' : 'LOSS',
                'quantity'       => $consumeQty,
                'remaining_qty'  => $request->reason === 'restock' ? $consumeQty : 0,
                'order_id'       => $order->id,
                'reference_type' => 'CREDIT_NOTE',
                'reference_id'   => $credit->id,
                'unit_price'     => $out->unit_price,
                'total_value'    => $consumeQty * $out->unit_price,
            ]);

            $remainingQty -= $consumeQty;
        }

        if ($remainingQty > 0) {
            throw new \Exception('FIFO mismatch: insufficient sold stock');
        }

        /* ===========================
           3️⃣ GST LEDGER
        =========================== */

        GstLedger::create([
            'branch'         => $branch,
            'type'           => 'OUT_REVERSAL',
            'taxable_amount' => $taxable,
            'gst_amount'     => $gstAmount,
            'reference_type' => 'CREDIT_NOTE',
            'reference_id'   => $credit->id,
        ]);

        /* ===========================
           4️⃣ ORDER CHANGE LOG
        =========================== */

        OrderChangeLog::create([
            'order_detail_id' => $detail->id,
            'returned_qty'    => $qtyToReturn,
            'credit_note_id'  => $credit->id,
            'processed_at'    => now(),
        ]);

        /* ===========================
           5️⃣ AUDIT LOG
        =========================== */

        AuditLog::create([
            'user_id'    => auth()->id(),
            'branch'     => $branch,
            'action'     => 'ORDER_ITEM_RETURN',
            'table_name' => 'orders',
            'record_id'  => $order->id,
            'new_values' => json_encode([
                'product_id' => $detail->product_id,
                'qty'        => $qtyToReturn,
                'credit_note'=> $creditNo,
                'batch_fifo' => true,
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        DB::commit();

        return back()->with('success', 'Return processed successfully');

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

}
