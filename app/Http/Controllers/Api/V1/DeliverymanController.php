<?php

namespace App\Http\Controllers\Api\V1;
use App\Services\StorePaymentFifoService;
use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\DeliveryHistory;
use App\Model\DeliveryMan;
use App\Model\Order;
use App\Traits\HelperTrait;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\DeliveryTrip;
use App\Model\OrderDetail;
use App\Model\OrderEditLog;

class DeliverymanController extends Controller
{
    use HelperTrait;
    public function __construct(
        private BusinessSetting $businessSetting,
        private DeliveryHistory $deliveryHistory,
        private DeliveryMan $deliveryman,
        private Order $order,
        private User $user
    ) {
    }

    public function orderEditLogs(Request $request)
    {
        $request->validate([
            'auth_token' => 'required'
        ]);

        // Authenticate delivery man
        $deliveryMan = DeliveryMan::where('auth_token', $request->auth_token)->first();

        if (!$deliveryMan) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        // Fetch logs
        $logs = OrderEditLog::with(['orderDetail.product', 'order'])
            ->where('delivery_man_id', $deliveryMan->id)
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json([
            'status' => true,
            'delivery_man' => [
                'id' => $deliveryMan->id,
                'name' => $deliveryMan->f_name . ' ' . $deliveryMan->l_name,
                'phone' => $deliveryMan->phone,
                'email' => $deliveryMan->email,
            ],
            'logs' => $logs
        ]);
    }
    public function reasons()
    {
        $reasons = [
            ['id' => 1, 'reason' => 'Quality issue'],
            ['id' => 2, 'reason' => 'Damage'],
            ['id' => 3, 'reason' => 'Shop closed'],
            ['id' => 4, 'reason' => 'Payment issue'],
            ['id' => 5, 'reason' => 'Customer changed mind'],
            ['id' => 6, 'reason' => 'Out of stock'],
            ['id' => 7, 'reason' => 'Wrong product ordered'],
            ['id' => 8, 'reason' => 'Partial return / qty change'],
            ['id' => 9, 'reason' => 'Customer removed Product']
        ];

        return response()->json([
            'status' => true,
            'data' => $reasons
        ]);
    }


    public function deleteOrderProduct(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'order_id' => 'required|integer',
            'order_detail_id' => 'required|integer',
            'reason' => 'required|string|max:255'
        ]);

        $deliveryman = DeliveryMan::where('auth_token', $request->token)->first();

        if (!$deliveryman) {
            return response()->json([
                'errors' => [['code' => '401', 'message' => 'Invalid token!']]
            ], 401);
        }

        $order = Order::find($request->order_id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $orderDetail = OrderDetail::find($request->order_detail_id);
        if (!$orderDetail) {
            return response()->json(['message' => 'Order item not found'], 404);
        }

      DB::beginTransaction();

try {

    // Log return
    OrderEditLog::create([
        'order_id' => $order->id,
        'order_detail_id' => $orderDetail->id,
        'delivery_man_id' => $deliveryman->id,
        'reason' => $request->reason,
        'old_quantity' => $orderDetail->quantity,
        'new_quantity' => 0,
        'old_price' => $orderDetail->price,
        'new_price' => $orderDetail->price,
        'is_returned' => 1
    ]);

            // ✅ AUDIT LOG
            DB::table('audit_logs')->insert([
                'user_id' => $deliveryman->id,
                'branch' => $order->branch_id ?? null,
                'action' => 'order_item_returned',
                'table_name' => 'order_details',
                'record_id' => $orderDetail->id,
                'old_values' => json_encode([
                    'quantity' => $orderDetail->quantity
                ]),
                'new_values' => json_encode([
                    'quantity' => 0
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // mark order partial delivered
            $order->order_status = 'partial_delivered';
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Item marked returned'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage()],500);
        }

    }



    public function editOrderProduct(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'order_id' => 'required',
            'items' => 'required|array|min:1',
            'items.*.order_detail_id' => 'required|integer',
            'items.*.new_quantity' => 'required|integer|min:1',
            'items.*.reason' => 'required|string|max:255',
            'items.*.photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);


        $deliveryman = DeliveryMan::where('auth_token', $request->token)->first();

        if (!$deliveryman) {
            return response()->json([
                'errors' => [['code' => '401', 'message' => 'Invalid token!']]
            ], 401);
        }

        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        DB::beginTransaction();

        try {
            foreach ($request->items as $index => $item) {

                $orderDetail = OrderDetail::find($item['order_detail_id']);

                if (!$orderDetail)
                    continue;

                // ✅ Handle photo upload per item
                $photoPath = null;
                if ($request->hasFile("items.$index.photo")) {
                    $photoPath = $request->file("items.$index.photo")->store('order_edit_photos', 'public');
                }

                // ✅ Store log
                OrderEditLog::create([
                    'order_id' => $order->id,
                    'order_detail_id' => $orderDetail->id,
                    'delivery_man_id' => $deliveryman->id,
                    'reason' => $item['reason'],
                    'old_quantity' => $orderDetail->quantity,
                    'new_quantity' => $item['new_quantity'],
                    'old_price' => $orderDetail->price * $orderDetail->quantity,
                    'new_price' => $orderDetail->price * $item['new_quantity'],
                    'photo' => $photoPath,
                ]);

                // ✅ Update quantity
             //   $orderDetail->quantity = $item['new_quantity'];
             //   $orderDetail->quantity = $item['new_quantity'];
                $orderDetail->save();


                $returnQty = $orderDetail->quantity - $item['new_quantity'];

if ($returnQty > 0) {

    OrderEditLog::create([
        'order_id' => $order->id,
        'order_detail_id' => $orderDetail->id,
        'delivery_man_id' => $deliveryman->id,
        'reason' => $item['reason'],
        'old_quantity' => $orderDetail->quantity,
        'new_quantity' => $item['new_quantity'],
        'old_price' => $orderDetail->price,
        'new_price' => $orderDetail->price,
        'photo' => $photoPath,
        'is_returned' => 1
    ]);

    // ✅ AUDIT LOG
    DB::table('audit_logs')->insert([
        'user_id' => $deliveryman->id,
        'branch' => $order->branch_id ?? null,
        'action' => 'partial_item_return',
        'table_name' => 'order_details',
        'record_id' => $orderDetail->id,
        'old_values' => json_encode([
            'quantity' => $orderDetail->quantity
        ]),
        'new_values' => json_encode([
            'quantity' => $item['new_quantity']
        ]),
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

            }


            // Recalculate order total
            $new_total = OrderDetail::where('order_id', $order->id)
                ->sum(DB::raw('price * quantity + tax_amount - discount_on_product'));

           $order->order_status = 'partial_delivered';
           $order->save();

            DB::commit();

            DB::table('audit_logs')->insert([
    'user_id' => $deliveryman->id,
    'branch' => $order->branch_id ?? null,
    'action' => 'order_status_changed',
    'table_name' => 'orders',
    'record_id' => $order->id,
    'old_values' => json_encode([
        'status' => $order->getOriginal('order_status')
    ]),
    'new_values' => json_encode([
        'status' => $request['status']
    ]),
    'created_at' => now(),
    'updated_at' => now()
]);


            return response()->json([
                'message' => 'Order updated successfully',
                'updated_order_total' => $new_total
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => '401', 'message' => 'Invalid token!']
                ]
            ], 401);
        }
        return response()->json($deliveryman, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
 

public function getCurrentOrders(Request $request): \Illuminate\Http\JsonResponse
{
    $validator = Validator::make($request->all(), [
        'token' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    }

    $deliveryman = $this->deliveryman
        ->where('auth_token', $request->token)
        ->first();

    if (!$deliveryman) {
        return response()->json([
            'errors' => [
                ['code' => '401', 'message' => 'Invalid token!']
            ]
        ], 401);
    }

    $orders = $this->order
        ->with([
            'delivery_address',
            'customer',
            'partial_payment',
            'order_image',
            'store'
        ])
        ->where('delivery_man_id', $deliveryman->id)
        ->get();

    $orders->transform(function ($order) {

        // If order amount 0 → reset taxes
        if ($order->order_amount == 0) {
            $order->update(['total_tax_amount' => 0]);
            $order->total_tax_amount = 0;
        }

        // If no delivery address, use store address
        if (empty($order->delivery_address) && $order->store) {
            $order->delivery_address = [
                'id' => $order->store->id,
                'address_type' => 'Store',
                'store_name' => $order->store->store_name,
                'contact_person_name' => $order->store->customer_name ?? null,
                'contact_person_number' => $order->store->phone_number ?? null,
                'address' => $order->store->address ?? null,
                'latitude' => $order->store->latitude,
                'longitude' => $order->store->longitude,
                'landmark' => $order->store->landmark ?? null
            ];
        }

      
        $result = DB::table('orders as o')
        ->leftJoin('order_payments as op', function ($join) {
    $join->on('o.id', '=', 'op.order_id')
         ->where('op.payment_status', 'complete'); 
})

            ->where('o.id', $order->id)
            ->selectRaw("
                o.order_amount,
                o.total_tax_amount,
                SUM(COALESCE(op.amount, 0)) AS total_paid,
                (o.order_amount + o.total_tax_amount - SUM(COALESCE(op.amount, 0))) AS pending_amount
            ")
            ->groupBy('o.id', 'o.order_amount', 'o.total_tax_amount')
            ->first();

        // Set final values
        $order->amount = $result->total_paid ?? 0;
        $order->arrear_amount = $result->pending_amount ?? 0;

        return $order;
    });

    return response()->json($orders, 200);
}



    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function recordLocationData(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => 'Invalid token!']
                ]
            ], 401);
        }
        DB::table('delivery_histories')->insert([
            'order_id' => $request['order_id'],
            'deliveryman_id' => $deliveryman['id'],
            'longitude' => $request['longitude'],
            'latitude' => $request['latitude'],
            'time' => now(),
            'location' => $request['location'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json(['message' => 'location recorded'], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => 'Invalid token!']
                ]
            ], 401);
        }

        $history = $this->deliveryHistory->where(['order_id' => $request['order_id'], 'deliveryman_id' => $deliveryman['id']])->get();
        return response()->json($history, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOrderStatus(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'token' => 'required',
        'order_id' => 'required|exists:orders,id',
        'status' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    }

    $token = str_replace('Bearer ', '', $request->token);

    $deliveryman = DeliveryMan::where('auth_token', $token)->first();
    if (!$deliveryman) {
        return response()->json([
            'errors' => [['code' => 'delivery-man', 'message' => 'Invalid token!']]
        ], 401);
    }

    $order = Order::where([
        'id' => $request->order_id,
        'delivery_man_id' => $deliveryman->id
    ])->first();

    if (!$order) {
        return response()->json([
            'errors' => [['code' => 'order', 'message' => 'Order not found!']]
        ], 404);
    }

    DB::beginTransaction();

    try {
        $oldStatus = $order->order_status;
        $newStatus = $request->status;

        // ---------------- UPDATE STATUS ----------------
        $order->order_status = $newStatus;
        $order->save();

        // ---------------- AUDIT LOG (STATUS CHANGE) ----------------
        DB::table('audit_logs')->insert([
            'user_id' => $deliveryman->id,
            'branch' => $order->branch_id,
            'action' => 'order_status_changed',
            'table_name' => 'orders',
            'record_id' => $order->id,
            'old_values' => json_encode(['order_status' => $oldStatus]),
            'new_values' => json_encode(['order_status' => $newStatus]),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // ---------------- STORE LEDGER (DEBIT ON DELIVERY) ----------------
        if ($newStatus === 'delivered' && $oldStatus !== 'delivered') {

            $orderTotal = $order->order_amount + $order->total_tax_amount;

            // Prevent duplicate debit
            $alreadyDebited = DB::table('store_ledgers')
                ->where('store_id', $order->store_id)
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->exists();

            if (!$alreadyDebited) {

                $lastBalance = DB::table('store_ledgers')
                    ->where('store_id', $order->store_id)
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0;

                DB::table('store_ledgers')->insert([
                    'store_id' => $order->store_id,
                    'order_id' => $order->id,
                    'entry_type' => 'debit',
                    'amount' => $orderTotal,
                    'balance_after' => $lastBalance - $orderTotal,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // ---------------- AUDIT LOG (LEDGER DEBIT) ----------------
                DB::table('audit_logs')->insert([
                    'user_id' => $deliveryman->id,
                    'branch' => $order->branch_id,
                    'action' => 'store_ledger_debit',
                    'table_name' => 'store_ledgers',
                    'record_id' => $order->id,
                    'new_values' => json_encode([
                        'amount' => $orderTotal,
                        'type' => 'debit'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Order status updated successfully',
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();

        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => 'Invalid token!']
                ]
            ], 401);
        }

        $order = $this->order
            ->with(['details'])
            ->where([
                'delivery_man_id' => $deliveryman['id'],
                'id' => $request['order_id']
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => 'Order not found!']
                ]
            ], 404);
        }

        $details = $order->details;

        foreach ($details as $detail) {
            $detail['add_on_ids'] = is_string($detail['add_on_ids'])
                ? json_decode($detail['add_on_ids'], true)
                : $detail['add_on_ids'];

            $detail['add_on_qtys'] = is_string($detail['add_on_qtys'])
                ? json_decode($detail['add_on_qtys'], true)
                : $detail['add_on_qtys'];

            // Handle variation data safely
            $variation = [];
            if (is_string($detail['variation']) && !empty($detail['variation'])) {
                $decoded = json_decode($detail['variation']);
                if (is_array($decoded)) {
                    $variation = $decoded;
                } else {
                    $variation[] = $decoded;
                }
            } elseif (is_array($detail['variation'])) {
                $variation = $detail['variation'];
            }
            $detail['variation'] = $variation;
            $detail['formatted_variation'] = $variation[0] ?? null;

            // Safely decode and patch product_details
            $productDetails = is_string($detail['product_details'])
                ? json_decode($detail['product_details'], true)
                : $detail['product_details'];

            // ✅ Prevent Undefined array key 'variations'
            if (!isset($productDetails['variations']) && isset($productDetails['variation'])) {
                $productDetails['variations'] = $productDetails['variation'];
            } elseif (!isset($productDetails['variations'])) {
                $productDetails['variations'] = [];
            }

            // ✅ Prevent Undefined array key 'translations'
            if (!isset($productDetails['translations'])) {
                $productDetails['translations'] = [];
            }

            $detail['product_details'] = Helpers::product_data_formatting($productDetails);
        }

        return response()->json($details, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => '401', 'message' => 'Invalid token!']
                ]
            ], 401);
        }
        $orders = $this->order->with(['delivery_address', 'customer'])->where(['delivery_man_id' => $deliveryman['id']])->get();
        return response()->json($orders, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLastLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $lastData = $this->deliveryHistory->where(['order_id' => $request['order_id']])->latest()->first();
        return response()->json($lastData, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function orderPaymentStatusUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => 'Invalid token!']
                ]
            ], 401);
        }

        if ($this->order->where(['delivery_man_id' => $deliveryman['id'], 'id' => $request['order_id']])->first()) {
            $this->order->where(['delivery_man_id' => $deliveryman['id'], 'id' => $request['order_id']])->update([
                'payment_status' => $request['status']
            ]);
            return response()->json(['message' => 'Payment status updated'], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => 'not found!']
            ]
        ], 404);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => 'Invalid token!']
                ]
            ], 401);
        }

        $this->deliveryman->where(['id' => $deliveryman['id']])->update([
            'fcm_token' => $request['fcm_token']
        ]);

        return response()->json(['message' => 'successfully updated!'], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function changeLanguage(Request $request): JsonResponse
    {
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();
        if (isset($deliveryman)) {
            $deliveryman->language_code = $request->language_code ?? 'en';
            $deliveryman->save();
        }
        return response()->json(['delivery_man' => $deliveryman], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function orderModel(Request $request): JsonResponse
    {
        $deliveryman = $this->deliveryman->where(['auth_token' => $request['token']])->first();

        if (!isset($deliveryman)) {
            return response()->json([
                'errors' => [['code' => 'delivery-man', 'message' => translate('Invalid token!')]]
            ], 401);
        }

        $order = $this->order
            ->with(['customer', 'partial_payment', 'order_image'])
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery'])
            ->where(['delivery_man_id' => $deliveryman['id'], 'id' => $request->id])
            ->first();

        return response()->json($order, 200);
    }

    public function getTrips(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate token
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Authenticate delivery man using auth_token
        $deliveryman = DeliveryMan::where('auth_token', $request->token)->first();
        if (!$deliveryman) {
            return response()->json([
                'errors' => [
                    ['code' => '401', 'message' => 'Invalid token!']
                ]
            ], 401);
        }

        // Fetch trips
        $trips = DeliveryTrip::where('delivery_man_id', $deliveryman->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trip) {

                // Decode order IDs
                $orderIds = is_string($trip->order_ids)
                    ? json_decode($trip->order_ids, true)
                    : (array) $trip->order_ids;

                // Fetch orders excluding delivered
                $orders = Order::whereIn('id', $orderIds)
                    ->where('order_status', '!=', 'delivered')
                    ->get([
                        'id',
                        'order_status',
                        'order_amount',
                        'payment_status',
                        'trip_number',
                        'sales_person_id',
                        'store_id'
                    ])
                    ->map(function ($order) {
                    // Fetch sales person
                    $salesPerson = \App\Models\SalesPerson::find($order->sales_person_id);

                    return [
                        'id' => $order->id,
                        'order_status' => $order->order_status,
                        'order_amount' => $order->order_amount,
                        'payment_status' => $order->payment_status,
                        'trip_number' => $order->trip_number,
                        'sales_person' => $salesPerson ? [
                            'id' => $salesPerson->id,
                            'name' => $salesPerson->name,
                            'phone_number' => $salesPerson->phone_number,
                            'email' => $salesPerson->email,
                        ] : null,
                    ];
                });

                return [
                    'trip_number' => $trip->trip_number,
                    'status' => $trip->status,
                    'orders' => $orders,
                    'created_at' => $trip->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $trip->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'delivery_man' => [
                'id' => $deliveryman->id,
                'name' => $deliveryman->f_name . ' ' . $deliveryman->l_name,
                'phone' => $deliveryman->phone,
                'email' => $deliveryman->email,
            ],
            'trips' => $trips
        ], 200);
    }




    public function updateTripStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        // Step 1: Validate input
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'trip_number' => 'required',
            'status' => 'required|in:pending,on_route,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // Step 2: Verify delivery man using token
        $deliveryman = DeliveryMan::where('auth_token', $request->token)->first();
        if (!$deliveryman) {
            return response()->json([
                'errors' => [
                    ['code' => '401', 'message' => 'Invalid token!']
                ]
            ], 401);
        }

        // Step 3: Find the trip belonging to this delivery man
        $trip = DeliveryTrip::where('trip_number', $request->trip_number)
            ->where('delivery_man_id', $deliveryman->id)
            ->first();

        if (!$trip) {
            return response()->json([
                'errors' => [
                    ['code' => '404', 'message' => 'Trip not found!']
                ]
            ], 404);
        }

        // Step 4: Update status
        $trip->status = $request->status;
        $trip->save();

        // Step 5: Return response
        return response()->json([
            'message' => 'Trip status updated successfully',
            'trip' => [
                'trip_number' => $trip->trip_number,
                'status' => $trip->status,
                'updated_at' => $trip->updated_at->format('Y-m-d H:i:s'),
            ]
        ], 200);
    }

    public function getTripStatuses(): \Illuminate\Http\JsonResponse
    {
        // You can define statuses directly here or fetch dynamically from the DB if needed
        $statuses = [
            'pending',
            'on_route',
            'completed'
        ];

        return response()->json([
            'success' => true,
            'statuses' => $statuses
        ], 200);
    }


    /**
     * @deprecated This method uses legacy OrderPayment. Use StorePaymentFifoService instead.
     * 
     * Store payment for order
     */
    public function store(Request $request)
    {
        // 1️⃣ Validate input
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:upi,credit_sale',
            'transaction_id' => 'required_if:payment_method,upi',
            'first_payment' => 'required_if:payment_method,credit_sale|numeric',
            'first_payment_date' => 'required_if:payment_method,credit_sale|date',
            'second_payment' => 'nullable|numeric',
            'second_payment_date' => 'nullable|date',
            'payment_status' => 'required|in:complete,incomplete',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 403);
        }

        // 2️⃣ Validate token (deliveryman or salesperson)
        $deliveryman = DeliveryMan::where('auth_token', $request->token)->first();
        if (!$deliveryman) {
            return response()->json([
                'errors' => [['code' => '401', 'message' => 'Invalid token!']]
            ], 401);
        }

        // 3️⃣ Check if order exists
        $order = Order::find($request->order_id);
        if (!$order) {
            return response()->json([
                'errors' => [['code' => '404', 'message' => 'Order not found!']]
            ], 404);
        }

        // 4️⃣ Create or update payment record
        $payment = OrderPayment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'first_payment' => $request->first_payment,
                'second_payment' => $request->second_payment,
                'first_payment_date' => $request->first_payment_date,
                'second_payment_date' => $request->second_payment_date,
                'payment_status' => $request->payment_status,
            ]
        );

        // 5️⃣ Update order based on payment status
        if ($payment->payment_status === 'complete') {
            $order->payment_status = 'paid';
            $order->order_status = 'delivered';
        } else {
            $order->payment_status = 'unpaid';
            // Keep the previous order_status or set to pending/processing if needed
        }
        $order->save();

        // 6️⃣ Return response
        return response()->json([
            'message' => 'Order payment recorded successfully',
            'payment' => $payment,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status
        ], 200);
    }
    /**
     * @deprecated This method uses legacy OrderPayment. Use StorePaymentFifoService instead.
     * 
     * Store flexible payment (multiple payment methods for one order)
     */
    public function storeFlexiblePayment(Request $request)
    {
        // -------------------- VALIDATION --------------------
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required|exists:orders,id',
            'payments' => 'required|array|min:1',

            'payments.*.payment_method' => 'required|in:cash,upi,credit_sale',

            // Amount required unless credit_sale because credit calculated automatically
            'payments.*.amount' => 'required_unless:payments.*.payment_method,credit_sale|numeric|min:0',

            'payments.*.payment_date' => 'nullable|date',
        ]);

        // Custom validation for UPI transaction_id based on amount
        $validator->after(function ($validator) use ($request) {
            foreach ($request->payments as $index => $payment) {
                if (
                    isset($payment['payment_method']) &&
                    $payment['payment_method'] === 'upi' &&
                    isset($payment['amount']) &&
                    $payment['amount'] > 0 &&
                    empty($payment['transaction_id'])
                ) {
                    $validator->errors()->add("payments.$index.transaction_id", "Transaction ID is required when UPI amount is greater than 0.");
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }


        // -------------------- AUTH CHECK --------------------
        $deliveryman = DeliveryMan::where('auth_token', str_replace('Bearer ', '', $request->token))->first();
        if (!$deliveryman) {
            return response()->json(['errors' => [['code' => 401, 'message' => 'Invalid token!']]], 401);
        }


        // -------------------- FETCH ORDER --------------------
        $order = Order::find($request->order_id);
        $orderTotal = (float) ($order->order_amount + $order->total_tax_amount);

        // Already paid before
        $alreadyPaid = OrderPayment::where('order_id', $order->id)
            ->where('payment_status', 'complete')
            ->sum('amount');


        // -------------------- PROCESS NEW PAYMENT --------------------
        $incomingPaid = 0;
        $validPayments = [];

        foreach ($request->payments as $payment) {

            // Add only real cash/upi payments (not credit)
            if ($payment['payment_method'] !== 'credit_sale') {
                $incomingPaid += $payment['amount'];
            }

            // Skip empty payment modes
            if (($payment['amount'] ?? 0) == 0 && $payment['payment_method'] !== 'credit_sale') {
                continue;
            }

            $validPayments[] = $payment;
        }

        if (empty($validPayments)) {
            return response()->json([
                'errors' => [['code' => 'payment', 'message' => 'At least one non-zero payment is required.']]
            ], 422);
        }


        // -------------------- PREVENT OVERPAYMENT --------------------
        if (($alreadyPaid + $incomingPaid) > $orderTotal) {
            return response()->json([
                'errors' => [['code' => 422, 'message' => 'Payment exceeds order total.']]
            ], 422);
        }


        // -------------------- SAVE PAYMENTS --------------------
        $paymentRecords = [];

       foreach ($validPayments as $pay) {
            if ($pay['payment_method'] === 'credit_sale') continue;

            StorePaymentFifoService::apply(
                storeId: $order->store_id,
                amount: $pay['amount'],
                method: $pay['payment_method'],
                txnId: $pay['transaction_id'] ?? null,
                userId: $deliveryman->id
            );
        }


        $totalPaid = $incomingPaid + $alreadyPaid;


        // -------------------- AUTO CREATE CREDIT SALE ENTRY --------------------
        if ($totalPaid < $orderTotal) {
            $remaining = $orderTotal - $totalPaid;

            $paymentRecords[] = OrderPayment::create([
                'order_id' => $order->id,
                'payment_method' => 'credit_sale',
                'amount' => $remaining,
                'payment_date' => now()->toDateString(),
                'payment_status' => 'incomplete',
            ]);
        }


        // -------------------- UPDATE ORDER PAYMENT STATUS --------------------
        if ($totalPaid == 0) {
            $order->payment_status = 'unpaid';
        } elseif ($totalPaid < $orderTotal) {
            $order->payment_status = 'partial';
        } else {
            $order->payment_status = 'paid';
        }

        $order->save();


        // -------------------- RESPONSE --------------------
        return response()->json([
            'message' => 'Payment recorded successfully',
            'order_id' => $order->id,
            'order_status' => $order->order_status,
            'order_payment_status' => $order->payment_status,
            'order_total' => $orderTotal,
            'total_paid' => $totalPaid,
            'due_amount' => max($orderTotal - $totalPaid, 0),
            'payments' => $paymentRecords
        ], 200);
    }
   

    public function getOrderPayments(Request $request)
    {
        // ----------- VALIDATION -----------
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // ----------- AUTH CHECK -----------
        $deliveryman = DeliveryMan::where('auth_token', str_replace('Bearer ', '', $request->token))->first();
        if (!$deliveryman) {
            return response()->json([
                'errors' => [['code' => 401, 'message' => 'Invalid token']]
            ], 401);
        }

        // ----------- FETCH ORDER -----------
        $order = Order::find($request->order_id);

        // Confirm that this delivery man belongs to the order (Optional depending on logic)
        if ($order->delivery_man_id != $deliveryman->id) {
            return response()->json([
                'errors' => [['code' => 403, 'message' => 'You are not assigned to this order']]
            ], 403);
        }

        // ----------- PAYMENT DETAILS -----------
        $orderTotal = (float) ($order->order_amount + $order->total_tax_amount);
        $paidAmount = $order->paid_amount ?? 0;

        // Get payments from payment_allocations joined with payment_ledgers
        $payments = DB::table('payment_allocations as pa')
            ->join('payment_ledgers as pl', 'pa.payment_ledger_id', '=', 'pl.id')
            ->where('pa.order_id', $order->id)
            ->select('pl.*', 'pa.allocated_amount')
            ->orderBy('pl.created_at', 'desc')
            ->get();

        // ----------- RESPONSE -----------
        return response()->json([
            'order_id' => $order->id,
            'order_status' => $order->order_status,
            'order_payment_status' => $order->payment_status,
            'order_total' => $orderTotal,
            'paid_amount' => $paidAmount,
            'due_amount' => max($orderTotal - $paidAmount, 0),
            'payments' => $payments
        ], 200);
    }






    public function index(Request $request)
    {
        // 1️⃣ Validate token
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 403);
        }

        // 2️⃣ Verify deliveryman or salesperson token
        $deliveryman = DeliveryMan::where('auth_token', $request->token)->first();
        if (!$deliveryman) {
            return response()->json([
                'errors' => [['code' => '401', 'message' => 'Invalid token!']]
            ], 401);
        }

        // 3️⃣ Fetch payment ledgers with allocations for orders assigned to this deliveryman
        $payments = DB::table('payment_ledgers as pl')
            ->join('payment_allocations as pa', 'pl.id', '=', 'pa.payment_ledger_id')
            ->join('orders as o', 'pa.order_id', '=', 'o.id')
            ->where('o.delivery_man_id', $deliveryman->id)
            ->select(
                'pl.id',
                'pl.transaction_ref',
                'pl.amount',
                'pl.payment_method',
                'pl.entry_type',
                'pl.created_at',
                'pa.order_id',
                'pa.allocated_amount'
            )
            ->orderBy('pl.created_at', 'desc')
            ->get();

        // 4️⃣ Return response
        return response()->json([
            'message' => 'Order payment list fetched successfully',
            'payments' => $payments
        ], 200);
    }
    public function getAllOrdersArrear(Request $request): JsonResponse
{
    
    $validator = Validator::make($request->all(), [
        'token' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => Helpers::error_processor($validator)
        ], 403);
    }

    
    $deliveryman = DeliveryMan::where('auth_token', $request->token)->first();

    if (!$deliveryman) {
        return response()->json([
            'errors' => [['code' => '401', 'message' => 'Invalid token!']]
        ], 401);
    }

    $orders = DB::table('orders')
        ->whereNotIn('order_status', ['cancelled', 'failed'])
        ->where('delivery_man_id', $deliveryman->id) // Filter by deliveryman
        ->selectRaw('
            id as order_id,
            order_amount,
            COALESCE(paid_amount, 0) as total_paid,
            (order_amount + COALESCE(total_tax_amount,0) - COALESCE(paid_amount,0)) as arrear_balance
        ')
        ->orderByDesc('id')
        ->get();

   
    return response()->json([
        'success' => true,
        'orders_count' => $orders->count(),
        'orders' => $orders
    ], 200);
}



    public function getPaymentMethods()
    {
        // Fetch distinct payment methods from payment_ledgers
        $methods = DB::table('payment_ledgers')
            ->select('payment_method')
            ->distinct()
            ->whereNotNull('payment_method')
            ->pluck('payment_method');

        return response()->json([
            'message' => 'Payment methods fetched successfully',
            'payment_methods' => $methods
        ], 200);
    }


}
