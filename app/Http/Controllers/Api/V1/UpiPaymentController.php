<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderDetail;
use App\Models\UpiTransaction;
use App\Models\Store;
use App\Models\Product;
use App\Models\SalesPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * UPI Payment Controller
 * 
 * Handles UPI payments for:
 * - Delivery Man (EXISTING - collecting payment from customer)
 * - Sales Person (NEW - ordering on behalf of store, payment FIRST then order)
 * - Store (NEW - self ordering)
 */
class UpiPaymentController extends Controller
{
    // =====================================================================
    // PUBLIC: GET UPI DETAILS
    // =====================================================================

    /**
     * Get merchant UPI details
     * GET /api/v1/upi/details
     */
    public function getUpiDetails(Request $request)
    {
        $upiId = config('payment.upi_id', env('MERCHANT_UPI_ID', '7909920500@ptaxis'));
        $merchantName = config('payment.merchant_name', env('MERCHANT_NAME', 'Golden Brown'));

        return response()->json([
            'success' => true,
            'data' => [
                'upi_id' => $upiId,
                'merchant_name' => $merchantName,
            ],
        ]);
    }

    // =====================================================================
    // DELIVERY MAN UPI PAYMENT (EXISTING - DO NOT MODIFY)
    // For collecting payment from customer after delivery
    // =====================================================================

    /**
     * Initiate UPI payment - Delivery Man
     * POST /api/v1/delivery-man/upi/initiate
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $deliveryMan = $request->attributes->get('delivery_man');
            $order = Order::find($request->order_id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Generate payment reference
            $paymentRef = 'UPI-DM-' . strtoupper(Str::random(8)) . '-' . time();
            
            $upiId = config('payment.upi_id', env('MERCHANT_UPI_ID', '7909920500@ptaxis'));
            $merchantName = config('payment.merchant_name', env('MERCHANT_NAME', 'Golden Brown'));

            // Create transaction
            $transaction = UpiTransaction::create([
                'payment_ref' => $paymentRef,
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'delivery_man_id' => $deliveryMan->id,
                'amount' => $request->amount,
                'upi_id' => $upiId,
                'merchant_name' => $merchantName,
                'status' => UpiTransaction::STATUS_INITIATED,
                'initiated_at' => now(),
                'expires_at' => now()->addMinutes(15),
            ]);

            Log::info('Delivery Man UPI Payment Initiated', [
                'payment_ref' => $paymentRef,
                'delivery_man_id' => $deliveryMan->id,
                'order_id' => $order->id,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'data' => [
                    'payment_ref' => $paymentRef,
                    'upi_id' => $upiId,
                    'merchant_name' => $merchantName,
                    'amount' => $request->amount,
                    'order_id' => $order->id,
                    'expires_at' => $transaction->expires_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Delivery Man UPI Initiate Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm UPI payment - Delivery Man
     * POST /api/v1/delivery-man/upi/confirm
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'payment_ref' => 'required|string',
            'status' => 'required|in:SUCCESS,FAILURE,PENDING,SUBMITTED,CANCELLED',
            'txn_id' => 'nullable|string|max:100',
            'response_code' => 'nullable|string|max:20',
            'approval_ref_no' => 'nullable|string|max:100',
        ]);

        try {
            $deliveryMan = $request->attributes->get('delivery_man');
            $transaction = UpiTransaction::findByPaymentRef($request->payment_ref);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment reference not found'
                ], 404);
            }

            // Check if already processed
            if ($transaction->isSuccessful() || $transaction->isFailed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already processed',
                    'data' => [
                        'status' => $transaction->status,
                        'order_id' => $transaction->order_id,
                    ],
                ], 400);
            }

            // Check expiry
            if ($transaction->hasExpired()) {
                $transaction->markAsExpired();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request expired',
                ], 400);
            }

            $status = strtoupper($request->status);

            if ($status === 'SUCCESS') {
                // Mark transaction as success
                $transaction->update([
                    'txn_id' => $request->txn_id,
                    'response_code' => $request->response_code ?? '00',
                    'approval_ref_no' => $request->approval_ref_no,
                    'status' => UpiTransaction::STATUS_SUCCESS_UNCONFIRMED,
                    'confirmed_at' => now(),
                    'confirmed_by' => $deliveryMan->id,
                ]);

                // Update order payment status
                $order = Order::find($transaction->order_id);
                if ($order) {
                    $order->update(['payment_status' => 'paid']);

                    // Create/Update order payment record
                    OrderPayment::updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'payment_method' => 'upi',
                            'transaction_id' => $request->txn_id,
                            'first_payment' => $transaction->amount,
                            'first_payment_date' => now(),
                            'payment_status' => 'complete',
                            'amount' => $transaction->amount,
                            'payment_date' => now(),
                        ]
                    );
                }

                Log::info('Delivery Man UPI Payment Success', [
                    'payment_ref' => $request->payment_ref,
                    'txn_id' => $request->txn_id,
                    'order_id' => $transaction->order_id,
                    'delivery_man_id' => $deliveryMan->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment confirmed successfully',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'txn_id' => $request->txn_id,
                        'status' => 'success',
                        'order_id' => $transaction->order_id,
                    ],
                ]);

            } elseif ($status === 'FAILURE') {
                $transaction->markAsFailed($request->response_code);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'status' => 'failed',
                    ],
                ]);

            } elseif (in_array($status, ['PENDING', 'SUBMITTED'])) {
                $transaction->markAsPending($request->txn_id);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment pending verification',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'status' => 'pending',
                        'txn_id' => $request->txn_id,
                    ],
                ]);

            } else {
                $transaction->markAsCancelled();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment cancelled',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'status' => 'cancelled',
                    ],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Delivery Man UPI Confirm Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================================
    // SALES PERSON UPI PAYMENT (NEW)
    // Payment FIRST → Then Order Created (like Swiggy/Zomato)
    // =====================================================================

    /**
     * Initiate UPI payment - Sales Person
     * POST /api/v1/sales/upi/initiate
     * 
     * Flow: Initiate → Open UPI App → Confirm (order created)
     */
    public function initiateSalesPerson(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:gpay,phonepe,paytm,upi',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // Get sales person from token
            $salesPerson = $this->getSalesPersonFromRequest($request);
            if (!$salesPerson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Sales person not found'
                ], 401);
            }

            $store = Store::find($request->store_id);
            
            // Generate payment reference
            $paymentRef = 'UPI-SP-' . strtoupper(Str::random(8)) . '-' . time();
            
            $upiId = config('payment.upi_id', env('MERCHANT_UPI_ID', '7909920500@ptaxis'));
            $merchantName = config('payment.merchant_name', env('MERCHANT_NAME', 'Golden Brown'));

            // Create transaction with cart items stored in notes
            $transaction = UpiTransaction::create([
                'payment_ref' => $paymentRef,
                'order_id' => null, // Order NOT created yet!
                'store_id' => $request->store_id,
                'sales_person_id' => $salesPerson->id,
                'delivery_man_id' => null,
                'amount' => $request->amount,
                'upi_id' => $upiId,
                'merchant_name' => $merchantName,
                'upi_app' => $request->payment_method,
                'status' => UpiTransaction::STATUS_INITIATED,
                'initiated_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'notes' => json_encode([
                    'cart_items' => $request->cart_items,
                    'store_name' => $store->name ?? $store->store_name ?? 'Store',
                ]),
            ]);

            Log::info('Sales Person UPI Payment Initiated', [
                'payment_ref' => $paymentRef,
                'sales_person_id' => $salesPerson->id,
                'store_id' => $request->store_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'data' => [
                    'payment_ref' => $paymentRef,
                    'upi_id' => $upiId,
                    'merchant_name' => $merchantName,
                    'amount' => $request->amount,
                    'expires_at' => $transaction->expires_at,
                    'transaction_note' => "Payment to $merchantName",
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Sales Person UPI Initiate Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm UPI payment - Sales Person
     * POST /api/v1/sales/upi/confirm
     * 
     * On SUCCESS: Creates order automatically
     * On FAILURE: No order created
     */
    public function confirmSalesPerson(Request $request)
    {
        $request->validate([
            'payment_ref' => 'required|string',
            'status' => 'required|in:SUCCESS,FAILURE,PENDING,SUBMITTED,CANCELLED',
            'txn_id' => 'nullable|string|max:100',
            'response_code' => 'nullable|string|max:20',
            'approval_ref_no' => 'nullable|string|max:100',
            // Order details (can also be retrieved from notes)
            'store_id' => 'nullable|exists:stores,id',
            'order_details' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $transaction = UpiTransaction::findByPaymentRef($request->payment_ref);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment reference not found',
                ], 404);
            }

            // Check if already processed
            if ($transaction->isSuccessful() || $transaction->isFailed()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already processed',
                    'data' => [
                        'status' => $transaction->status,
                        'order_id' => $transaction->order_id,
                    ],
                ], 400);
            }

            // Check expiry
            if ($transaction->hasExpired()) {
                $transaction->markAsExpired();
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment request expired',
                ], 400);
            }

            $status = strtoupper($request->status);

            if ($status === 'SUCCESS') {
                // ✅ PAYMENT SUCCESS → CREATE ORDER NOW
                
                $salesPerson = $this->getSalesPersonFromRequest($request);
                if (!$salesPerson) {
                    // Try to get from transaction
                    $salesPerson = SalesPerson::find($transaction->sales_person_id);
                }
                
                if (!$salesPerson) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Sales person not found'
                    ], 401);
                }

                // Create the order
                $orderResult = $this->createOrderForSalesPerson(
                    $request,
                    $transaction,
                    $salesPerson
                );

                if (!$orderResult['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $orderResult['message'],
                    ], 500);
                }

                $order = $orderResult['order'];

                // Update transaction
                $transaction->update([
                    'order_id' => $order->id,
                    'txn_id' => $request->txn_id,
                    'response_code' => $request->response_code ?? '00',
                    'approval_ref_no' => $request->approval_ref_no,
                    'status' => UpiTransaction::STATUS_SUCCESS_UNCONFIRMED,
                    'confirmed_at' => now(),
                ]);

                // Create order payment record
                OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method' => $transaction->upi_app ?? 'upi',
                    'transaction_id' => $request->txn_id,
                    'first_payment' => $transaction->amount,
                    'first_payment_date' => now(),
                    'payment_status' => 'complete',
                    'amount' => $transaction->amount,
                    'payment_date' => now(),
                ]);

                DB::commit();

                Log::info('Sales Person UPI Payment Success - Order Created', [
                    'payment_ref' => $request->payment_ref,
                    'txn_id' => $request->txn_id,
                    'order_id' => $order->id,
                    'sales_person_id' => $salesPerson->id,
                    'amount' => $transaction->amount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful. Order placed!',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'txn_id' => $request->txn_id,
                        'status' => 'success',
                        'order' => [
                            'id' => $order->id,
                            'order_amount' => $order->order_amount,
                            'payment_status' => 'paid',
                        ],
                    ],
                ]);

            } elseif ($status === 'FAILURE') {
                // ❌ PAYMENT FAILED → NO ORDER CREATED
                $transaction->markAsFailed($request->response_code);
                DB::commit();

                Log::info('Sales Person UPI Payment Failed', [
                    'payment_ref' => $request->payment_ref,
                    'response_code' => $request->response_code,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed. No order created.',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'status' => 'failed',
                    ],
                ]);

            } elseif (in_array($status, ['PENDING', 'SUBMITTED'])) {
                // ⏳ PAYMENT PENDING
                $transaction->markAsPending($request->txn_id);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment pending verification',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'status' => 'pending',
                        'txn_id' => $request->txn_id,
                    ],
                ]);

            } else {
                // CANCELLED
                $transaction->markAsCancelled();
                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment cancelled. No order created.',
                    'data' => [
                        'payment_ref' => $request->payment_ref,
                        'status' => 'cancelled',
                    ],
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sales Person UPI Confirm Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create order after successful sales person payment
     */
    private function createOrderForSalesPerson(Request $request, UpiTransaction $transaction, $salesPerson): array
    {
        try {
            // Get cart items from request or from transaction notes
            $notesData = json_decode($transaction->notes, true) ?? [];
            $cartItems = $request->order_details ?? $notesData['cart_items'] ?? [];

            if (empty($cartItems)) {
                return ['success' => false, 'message' => 'No cart items found'];
            }

            // Calculate order amount
            $orderAmount = 0;
            $orderDetails = [];

            foreach ($cartItems as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    continue;
                }

                $price = $product->price;
                $discount = $product->discount ?? 0;
                $discountedPrice = $discount > 0 
                    ? $price * (1 - $discount / 100) 
                    : $price;
                
                // Apply 10% global discount
                $finalPrice = $discountedPrice * 0.9;
                $lineTotal = $finalPrice * $item['quantity'];
                $orderAmount += $lineTotal;

                $orderDetails[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $finalPrice,
                    'discount_on_product' => $discount + 10,
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => $salesPerson->user_id ?? null,
                'store_id' => $transaction->store_id,
                'sales_person_id' => $salesPerson->id,
                'order_amount' => $orderAmount,
                'order_status' => 'pending',
                'payment_method' => $transaction->upi_app ?? 'upi',
                'payment_status' => 'paid',
                'transaction_reference' => $transaction->payment_ref,
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
                    'discount_on_product' => $detail['discount_on_product'],
                ]);
            }

            return ['success' => true, 'order' => $order];

        } catch (\Exception $e) {
            Log::error('Create Order For Sales Person Error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =====================================================================
    // STORE SELF APP UPI PAYMENT (NEW)
    // =====================================================================

    /**
     * Initiate UPI payment - Store Self App
     * POST /api/v1/store/upi/initiate
     */
    public function initiateStore(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:gpay,phonepe,paytm,upi',
            'cart_items' => 'required|array|min:1',
        ]);

        try {
            $store = $request->attributes->get('store');
            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not authenticated'
                ], 401);
            }

            $paymentRef = 'UPI-ST-' . strtoupper(Str::random(8)) . '-' . time();
            $upiId = config('payment.upi_id', env('MERCHANT_UPI_ID', '7909920500@ptaxis'));
            $merchantName = config('payment.merchant_name', env('MERCHANT_NAME', 'Golden Brown'));

            $transaction = UpiTransaction::create([
                'payment_ref' => $paymentRef,
                'order_id' => null,
                'store_id' => $store->id,
                'amount' => $request->amount,
                'upi_id' => $upiId,
                'merchant_name' => $merchantName,
                'upi_app' => $request->payment_method,
                'status' => UpiTransaction::STATUS_INITIATED,
                'initiated_at' => now(),
                'expires_at' => now()->addMinutes(15),
                'notes' => json_encode(['cart_items' => $request->cart_items]),
            ]);

            Log::info('Store UPI Payment Initiated', [
                'payment_ref' => $paymentRef,
                'store_id' => $store->id,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'data' => [
                    'payment_ref' => $paymentRef,
                    'upi_id' => $upiId,
                    'merchant_name' => $merchantName,
                    'amount' => $request->amount,
                    'expires_at' => $transaction->expires_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm UPI payment - Store Self App
     * POST /api/v1/store/upi/confirm
     */
    public function confirmStore(Request $request)
    {
        $request->validate([
            'payment_ref' => 'required|string',
            'status' => 'required|in:SUCCESS,FAILURE,PENDING,SUBMITTED,CANCELLED',
            'txn_id' => 'nullable|string',
            'order_details' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $store = $request->attributes->get('store');
            $transaction = UpiTransaction::findByPaymentRef($request->payment_ref);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            if ($transaction->isSuccessful() || $transaction->isFailed()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already processed',
                ], 400);
            }

            $status = strtoupper($request->status);

            if ($status === 'SUCCESS') {
                // Create order for store
                $orderResult = $this->createOrderForStore($request, $transaction, $store);

                if (!$orderResult['success']) {
                    DB::rollBack();
                    return response()->json($orderResult, 500);
                }

                $order = $orderResult['order'];

                $transaction->update([
                    'order_id' => $order->id,
                    'txn_id' => $request->txn_id,
                    'status' => UpiTransaction::STATUS_SUCCESS_UNCONFIRMED,
                    'confirmed_at' => now(),
                ]);

                OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method' => $transaction->upi_app ?? 'upi',
                    'transaction_id' => $request->txn_id,
                    'first_payment' => $transaction->amount,
                    'first_payment_date' => now(),
                    'payment_status' => 'complete',
                    'amount' => $transaction->amount,
                    'payment_date' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful. Order placed!',
                    'data' => [
                        'txn_id' => $request->txn_id,
                        'order' => [
                            'id' => $order->id,
                            'payment_status' => 'paid',
                        ],
                    ],
                ]);

            } else {
                if ($status === 'FAILURE') {
                    $transaction->markAsFailed();
                } elseif (in_array($status, ['PENDING', 'SUBMITTED'])) {
                    $transaction->markAsPending($request->txn_id);
                } else {
                    $transaction->markAsCancelled();
                }

                DB::commit();

                return response()->json([
                    'success' => $status === 'PENDING' || $status === 'SUBMITTED',
                    'message' => $status === 'PENDING' || $status === 'SUBMITTED' 
                        ? 'Payment pending' 
                        : 'Payment ' . strtolower($status),
                    'data' => [
                        'status' => strtolower($status),
                    ],
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create order for store self app
     */
    private function createOrderForStore(Request $request, UpiTransaction $transaction, $store): array
    {
        try {
            $notesData = json_decode($transaction->notes, true) ?? [];
            $cartItems = $request->order_details ?? $notesData['cart_items'] ?? [];

            if (empty($cartItems)) {
                return ['success' => false, 'message' => 'No cart items found'];
            }

            $orderAmount = 0;
            $orderDetails = [];

            foreach ($cartItems as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $finalPrice = $product->price * 0.9;
                $orderAmount += $finalPrice * $item['quantity'];

                $orderDetails[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $finalPrice,
                ];
            }

            $order = Order::create([
                'store_id' => $store->id,
                'order_amount' => $orderAmount,
                'order_status' => 'pending',
                'payment_method' => $transaction->upi_app ?? 'upi',
                'payment_status' => 'paid',
                'transaction_reference' => $transaction->payment_ref,
            ]);

            foreach ($orderDetails as $detail) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                ]);
            }

            return ['success' => true, 'order' => $order];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =====================================================================
    // COMMON METHODS (Used by all)
    // =====================================================================

    /**
     * Cancel pending payment
     * POST /api/v1/sales/upi/cancel
     * POST /api/v1/store/upi/cancel
     * POST /api/v1/delivery-man/upi/cancel
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'payment_ref' => 'required|string',
        ]);

        $transaction = UpiTransaction::findByPaymentRef($request->payment_ref);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        if (!$transaction->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be cancelled (already processed)'
            ], 400);
        }

        $transaction->markAsCancelled();

        Log::info('UPI Payment Cancelled', [
            'payment_ref' => $request->payment_ref,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled',
        ]);
    }

    /**
     * Check payment status
     * GET /api/v1/sales/upi/status/{payment_ref}
     * GET /api/v1/store/upi/status/{payment_ref}
     * GET /api/v1/delivery-man/upi/status/{payment_ref}
     */
    public function status($paymentRef)
    {
        $transaction = UpiTransaction::findByPaymentRef($paymentRef);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment_ref' => $transaction->payment_ref,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'amount' => $transaction->amount,
                'txn_id' => $transaction->txn_id,
                'order_id' => $transaction->order_id,
                'created_at' => $transaction->created_at,
                'confirmed_at' => $transaction->confirmed_at,
            ],
        ]);
    }

    /**
     * Admin: Mark payment as settled after bank reconciliation
     * POST /api/v1/admin/upi/settle
     */
    public function markAsSettled(Request $request)
    {
        $request->validate([
            'payment_ref' => 'required|string',
            'bank_reference' => 'nullable|string|max:100',
        ]);

        $transaction = UpiTransaction::findByPaymentRef($request->payment_ref);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        if (!$transaction->needsSettlement()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be settled (status: ' . $transaction->status . ')'
            ], 400);
        }

        $transaction->markAsSettled($request->bank_reference);

        Log::info('UPI Payment Settled', [
            'payment_ref' => $request->payment_ref,
            'bank_reference' => $request->bank_reference,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as settled',
        ]);
    }

    // =====================================================================
    // HELPER METHODS
    // =====================================================================

    /**
     * Get sales person from request (token)
     * Uses auth_token column from sales_people table
     */
    private function getSalesPersonFromRequest(Request $request)
    {
        // Method 1: Check if already set in request attributes
        if ($request->attributes->has('sales_person')) {
            return $request->attributes->get('sales_person');
        }

        // Method 2: Get from Authorization header
        $token = $request->header('Authorization');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            
            // Check in sales_people table using auth_token column
            $salesPerson = SalesPerson::where('auth_token', $token)->first();
            
            if ($salesPerson) {
                return $salesPerson;
            }
        }

        return null;
    }
}