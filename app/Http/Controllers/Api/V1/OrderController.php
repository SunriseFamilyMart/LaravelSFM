<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Http\Controllers\Controller;
use App\Mail\Customer\OrderPlaced;
use App\Model\Coupon;
use App\Model\CustomerAddress;
use App\Model\DMReview;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Review;
use App\Models\GuestUser;
use App\Models\OfflinePayment;
use App\Models\OrderArea;
use App\Models\OrderImage;
use App\Models\OrderPartialPayment;
use App\Traits\HelperTrait;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use function App\CentralLogics\translate;

class OrderController extends Controller
{
    use HelperTrait;
    public function __construct(
        private Coupon $coupon,
        private DMReview $deliverymanReview,
        private Order $order,
        private OrderDetail $orderDetail,
        private Product $product,
        private Review $review,
        private User $user,
        private OrderArea $orderArea
    ){}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function trackOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'phone' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $phone = $request->input('phone');
        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request->header('guest-id');
        $userType = (bool)auth('api')->user() ? 0 : 1;

        $order = $this->order->find($request['order_id']);

        if (!isset($order)){
            return response()->json([
                'errors' => [['code' => 'order', 'message' => 'Order not found!']]], 404);
        }

        if (!is_null($phone)){
            if ($order['is_guest'] == 0){
                $trackOrder = $this->order
                    ->with(['customer', 'delivery_address'])
                    ->where(['id' => $request['order_id']])
                    ->whereHas('customer', function ($customerSubQuery) use ($phone) {
                        $customerSubQuery->where('phone', $phone);
                    })
                    ->first();
            }else{
                $trackOrder = $this->order
                    ->with(['delivery_address'])
                    ->where(['id' => $request['order_id']])
                    ->whereHas('delivery_address', function ($addressSubQuery) use ($phone) {
                        $addressSubQuery->where('contact_person_number', $phone);
                    })
                    ->first();
            }
        }else{
            $trackOrder = $this->order
                ->where(['id' => $request['order_id'], 'user_id' => $userId, 'is_guest' => $userType])
                ->first();
        }

        if (!isset($trackOrder)){
            return response()->json([
                'errors' => [['code' => 'order', 'message' => 'Order not found!']]], 404);
        }

        return response()->json(OrderLogic::track_order($request['order_id']), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function placeOrder(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'cart' => 'required|array|min:1',
        'branch_id' => 'required',
        'payment_method' => 'required',
        'order_amount' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    }

    $storeId = auth('api')->user()->store_id ?? null;
    if (!$storeId) {
        return response()->json(['errors' => [['message' => 'Store not found']]], 403);
    }

    /* ================= CREDIT LIMIT CHECK ================= */
    if ($request->payment_method === 'cash_on_delivery') {
        $creditLimit = DB::table('stores')->where('id', $storeId)->value('credit_limit') ?? 0;

        $outstanding = DB::table('store_ledgers')
            ->where('store_id', $storeId)
            ->selectRaw('SUM(debit - credit) as balance')
            ->value('balance') ?? 0;

        if (($outstanding + $request->order_amount) > $creditLimit) {
            return response()->json([
                'errors' => [['message' => 'Credit limit exceeded']]
            ], 403);
        }
    }

    try {
        DB::beginTransaction();

        $orderId = Order::max('id') + 1;

        DB::table('orders')->insert([
            'id' => $orderId,
            'store_id' => $storeId,
            'branch_id' => $request->branch_id,
            'order_amount' => $request->order_amount,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_method === 'cash_on_delivery' ? 'unpaid' : 'paid',
            'order_status' => 'pending',
            'created_at' => now(),
        ]);

        DB::table('audit_logs')->insert([
        'user_id' => $userId,
        'branch' => $request->branch_id,
        'action' => 'order_created',
        'table_name' => 'orders',
        'record_id' => $orderId,   // ← VERY IMPORTANT
        'old_values' => null,
        'new_values' => json_encode([
            'order_amount' => $or['order_amount'],
            'payment_method' => $or['payment_method'],
            'payment_status' => $or['payment_status'],
            'order_status' => $or['order_status'],
        ]),
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

        /* ================= INVENTORY FIFO ================= */
        foreach ($request->cart as $item) {
            $qty = $item['quantity'];

            $batches = DB::table('inventory_transactions')
                ->where('product_id', $item['product_id'])
                ->where('branch', $request->branch_id)
                ->where('remaining_qty', '>', 0)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($qty <= 0) break;

                $take = min($qty, $batch->remaining_qty);

                DB::table('inventory_transactions')->insert([
                    'product_id' => $item['product_id'],
                    'branch' => $request->branch_id,
                    'type' => 'OUT',
                    'quantity' => $take,
                    'unit_price' => $batch->unit_price,
                    'total_value' => $take * $batch->unit_price,
                    'batch_id' => $batch->batch_id,
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'created_at' => now(),
                ]);

                DB::table('inventory_transactions')
                    ->where('id', $batch->id)
                    ->update([
                        'remaining_qty' => $batch->remaining_qty - $take
                    ]);

                $qty -= $take;
            }
        }

        /* ================= STORE LEDGER (DEBIT) ================= */
        DB::table('store_ledgers')->insert([
            'store_id' => $storeId,
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'debit' => $request->order_amount,
            'credit' => 0,
            'remarks' => 'Order placed',
            'created_at' => now(),
        ]);

        /* ================= PAYMENT FIFO (IF PAID) ================= */
        if ($request->payment_method !== 'cash_on_delivery') {

            DB::table('store_ledgers')->insert([
                'store_id' => $storeId,
                'reference_type' => 'payment',
                'reference_id' => $orderId,
                'debit' => 0,
                'credit' => $request->order_amount,
                'remarks' => 'Payment received',
                'created_at' => now(),
            ]);

            $remaining = $request->order_amount;

            $pendingOrders = DB::table('orders')
                ->where('store_id', $storeId)
                ->where('payment_status', '!=', 'paid')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($pendingOrders as $old) {
                if ($remaining <= 0) break;

                $paid = $old->paid_amount ?? 0;
                $due = $old->order_amount - $paid;

                $adjust = min($due, $remaining);

                DB::table('orders')->where('id', $old->id)->update([
                    'paid_amount' => $paid + $adjust,
                    'payment_status' => ($paid + $adjust >= $old->order_amount) ? 'paid' : 'partial',
                ]);

                $remaining -= $adjust;
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Order placed successfully',
            'order_id' => $orderId
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * @param $orderImages
     * @param $orderId
     * @return true
     */
    private function uploadOrderImage($orderImages, $orderId): bool
    {
        foreach ($orderImages as $image) {
            $image = Helpers::upload('order/', 'png', $image);
            $orderImage = new OrderImage();
            $orderImage->order_id = $orderId;
            $orderImage->image = $image;
            $orderImage->save();
        }
        return true;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderList(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request->header('guest-id');
        $userType = (bool)auth('api')->user() ? 0 : 1;

        $orders = $this->order->with(['customer', 'delivery_man.rating', 'details:id,order_id,quantity'])
            ->where(['user_id' => $userId, 'is_guest' => $userType])
            ->get();

        $orders->each(function ($order) {
            $order->total_quantity = $order->details->sum('quantity');
        });

        $orders->map(function ($data) {
            $data['deliveryman_review_count'] = $this->deliverymanReview->where(['delivery_man_id' => $data['delivery_man_id'], 'order_id' => $data['id']])->count();
            return $data;
        });

        return response()->json($orders->map(function ($data) {
            $data->details_count = (integer)$data->details_count;
            return $data;
        }), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'phone' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $phone = $request->input('phone');
        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request->header('guest-id');
        $userType = (bool)auth('api')->user() ? 0 : 1;

        $order = $this->order->find($request['order_id']);
        if (!isset($order)){
            return response()->json([
                'errors' => [['code' => 'order', 'message' => 'Order not found!']]], 404);
        }

        if (!is_null($phone)){
            if ($order['is_guest'] == 0){
                $details = $this->orderDetail
                    ->with(['order', 'order.delivery_address' ,'order.customer', 'order.payments', 'order.offline_payment', 'order.order_image'])
                    ->where(['order_id' => $request['order_id']])
                    ->whereHas('order.customer', function ($customerSubQuery) use ($phone) {
                        $customerSubQuery->where('phone', $phone);
                    })
                    ->get();
            }else{
                $details = $this->orderDetail
                    ->with(['order', 'order.delivery_address', 'order.payments', 'order.offline_payment', 'order.order_image'])
                    ->where(['order_id' => $request['order_id']])
                    ->whereHas('order.delivery_address', function ($addressSubQuery) use ($phone) {
                        $addressSubQuery->where('contact_person_number', $phone);
                    })
                    ->get();
            }
        }else{
            $details = $this->orderDetail
                ->with(['order', 'order.payments', 'order.offline_payment'])
                ->where(['order_id' => $request['order_id']])
                ->whereHas('order', function ($q) use ($userId, $userType){
                    $q->where(['user_id' => $userId, 'is_guest' => $userType]);
                })
                ->orderBy('id', 'desc')
                ->get();
        }


        if ($details->count() > 0) {
            foreach ($details as $detail) {

                $keepVariation = $detail['variation'];

                $variation = json_decode($detail['variation'], true);

                $detail['add_on_ids'] = json_decode($detail['add_on_ids']);
                $detail['add_on_qtys'] = json_decode($detail['add_on_qtys']);
                if (gettype(json_decode($keepVariation)) == 'array'){
                    $new_variation = json_decode($keepVariation);
                }else{
                    $new_variation = [];
                    $new_variation[] = json_decode($detail['variation']);

                }

                $detail['variation'] = $new_variation;

//                $detail['formatted_variation'] = $new_variation[0] ?? null;
//                if (isset($new_variation[0]) && $new_variation[0]->type == null){
//                    $detail['formatted_variation'] = null;
//                }

                if (is_null($new_variation)) {
                    $detail['formatted_variation'] = null;
                } elseif (is_array($new_variation) && isset($new_variation[0])) {
                    $detail['formatted_variation'] = $new_variation[0];
                    if (isset($new_variation[0]->type) && $new_variation[0]->type == null) {
                        $detail['formatted_variation'] = null;
                    }
                } elseif (is_object($new_variation)) {
                    $detail['formatted_variation'] = $new_variation;
                } else {
                    $detail['formatted_variation'] = null;
                }

                $detail['review_count'] = $this->review->where(['order_id' => $detail['order_id'], 'product_id' => $detail['product_id']])->count();
                $detail['product_details'] = Helpers::product_data_formatting(json_decode($detail['product_details'], true));

            }
            return response()->json($details, 200);
        } else {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => 'Order not found!']
                ]
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $order = $this->order::find($request['order_id']);

        if (!isset($order)){
            return response()->json(['errors' => [['code' => 'order', 'message' => 'Order not found!']]], 404);
        }

        if ($order->order_status != 'pending'){
            return response()->json(['errors' => [['code' => 'order', 'message' => 'Order can only cancel when order status is pending!']]], 403);
        }

        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request->header('guest-id');
        $userType = (bool)auth('api')->user() ? 0 : 1;

        if ($this->order->where(['user_id' => $userId, 'is_guest' => $userType, 'id' => $request['order_id']])->first()) {

            $order = $this->order->with(['details'])->where(['user_id' => $userId, 'is_guest' => $userType, 'id' => $request['order_id']])->first();

            foreach ($order->details as $detail) {
                if ($detail['is_stock_decreased'] == 1) {
                    $product = $this->product->find($detail['product_id']);
                    if (isset($product)){
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

                        $this->orderDetail->where(['id' => $detail['id']])->update([
                            'is_stock_decreased' => 0,
                        ]);
                    }
                }
            }
            $this->order->where(['user_id' => $userId, 'is_guest' => $userType, 'id' => $request['order_id']])->update([
                'order_status' => 'canceled',
            ]);
            return response()->json(['message' => 'Order canceled'], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => 'not found!'],
            ],
        ], 401);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePaymentMethod(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request->header('guest-id');
        $userType = (bool)auth('api')->user() ? 0 : 1;

        if ($this->order->where(['user_id' => $userId, 'is_guest' => $userType, 'id' => $request['order_id']])->first()) {
            $this->order->where(['user_id' => $userId, 'is_guest' => $userType, 'id' => $request['order_id']])->update([
                'payment_method' => $request['payment_method'],
            ]);
            return response()->json(['message' => 'Payment method is updated.'], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => 'not found!'],
            ],
        ], 401);
    }
}
