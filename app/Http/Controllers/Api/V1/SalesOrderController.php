<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sales Order Controller
 * 
 * Handles order placement with integrated payment tracking.
 * Follows real delivery app flow:
 * - COD: Order placed with payment_status = 'pending'
 * - UPI: Payment completed FIRST, then order placed with payment_status = 'paid'
 */
class SalesOrderController extends Controller
{
    /**
     * Place a new order
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'order_details' => 'required|array|min:1',
            'order_details.*.product_id' => 'required|exists:products,id',
            'order_details.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cod,gpay,phonepe,paytm,upi',
            'payment_status' => 'required|in:paid,pending',
            'transaction_id' => 'nullable|string|max:100',
            'transaction_ref' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Calculate order total
            $orderAmount = 0;
            $orderDetails = [];

            foreach ($request->order_details as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    continue;
                }

                $price = $product->price;
                $discount = $product->discount ?? 0;
                $discountedPrice = $discount > 0 
                    ? $price * (1 - $discount / 100) 
                    : $price;
                
                // Apply global 10% discount
                $finalPrice = $discountedPrice * 0.9;
                $lineTotal = $finalPrice * $item['quantity'];
                $orderAmount += $lineTotal;

                $orderDetails[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $finalPrice,
                    'discount' => $discount + 10, // Backend + Global discount
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'store_id' => $request->store_id,
                'order_amount' => $orderAmount,
                'order_status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status,
                'transaction_reference' => $request->transaction_ref,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create order details
            foreach ($orderDetails as $detail) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'discount_on_product' => $detail['discount'],
                ]);
            }

            // Create payment record
            OrderPayment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status,
                'transaction_id' => $request->transaction_id,
                'transaction_ref' => $request->transaction_ref,
                'amount' => $orderAmount,
                'paid_at' => $request->payment_status === 'paid' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log payment for audit
            Log::info('Order Payment', [
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status,
                'transaction_id' => $request->transaction_id,
                'amount' => $orderAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order' => [
                    'id' => $order->id,
                    'order_amount' => $orderAmount,
                    'payment_method' => $request->payment_method,
                    'payment_status' => $request->payment_status,
                    'transaction_id' => $request->transaction_id,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Creation Failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify payment status (can be called by webhook or manually)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'transaction_id' => 'required|string',
            'status' => 'required|in:success,failed,pending',
        ]);

        $order = Order::find($request->order_id);
        $payment = OrderPayment::where('order_id', $request->order_id)->first();

        if (!$order || !$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Order or payment not found',
            ], 404);
        }

        // Update payment status
        $newStatus = $request->status === 'success' ? 'paid' : $request->status;
        
        $payment->update([
            'payment_status' => $newStatus,
            'transaction_id' => $request->transaction_id,
            'paid_at' => $newStatus === 'paid' ? now() : null,
            'updated_at' => now(),
        ]);

        $order->update([
            'payment_status' => $newStatus,
            'updated_at' => now(),
        ]);

        Log::info('Payment Verified', [
            'order_id' => $order->id,
            'transaction_id' => $request->transaction_id,
            'status' => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated',
            'order' => [
                'id' => $order->id,
                'payment_status' => $newStatus,
            ],
        ]);
    }

    /**
     * Get UPI merchant details
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpiDetails()
    {
        // You can fetch this from database/config
        $upiDetails = [
            'upi_id' => config('payment.upi_id', '7909920500@ptaxis'),
            'merchant_name' => config('payment.merchant_name', 'Golden Brown'),
        ];

        return response()->json([
            'success' => true,
            'data' => $upiDetails,
        ]);
    }
}