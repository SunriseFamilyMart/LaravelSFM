<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\Order;
use App\Models\PaymentLedger;
use App\Models\PaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnlinePaymentController extends Controller
{
    /**
     * Get UPI and Bank details for payment screen
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpiDetails()
    {
        try {
            // Fetch UPI details
            $upiId = BusinessSetting::where('key', 'upi_id')->first();
            $merchantName = BusinessSetting::where('key', 'upi_merchant_name')->first();
            
            // Fetch Bank details (optional)
            $bankName = BusinessSetting::where('key', 'bank_name')->first();
            $accountNumber = BusinessSetting::where('key', 'bank_account_number')->first();
            $ifscCode = BusinessSetting::where('key', 'bank_ifsc_code')->first();
            $accountHolder = BusinessSetting::where('key', 'bank_account_holder')->first();
            
            return response()->json([
                'success' => true,
                'message' => 'UPI details fetched successfully',
                'data' => [
                    'upi_details' => [
                        'upi_id' => $upiId->value ?? '',
                        'merchant_name' => $merchantName->value ?? 'Store',
                        'is_configured' => !empty($upiId->value),
                    ],
                    'bank_details' => [
                        'bank_name' => $bankName->value ?? '',
                        'account_number' => $accountNumber->value ?? '',
                        'ifsc_code' => $ifscCode->value ?? '',
                        'account_holder' => $accountHolder->value ?? '',
                        'is_configured' => !empty($accountNumber->value),
                    ],
                    'supported_apps' => [
                        [
                            'name' => 'Google Pay',
                            'code' => 'gpay',
                            'scheme' => 'tez://upi/pay',
                            'package' => 'com.google.android.apps.nbu.paisa.user',
                            'icon' => 'gpay',
                            'color' => '#4285F4',
                        ],
                        [
                            'name' => 'PhonePe',
                            'code' => 'phonepe',
                            'scheme' => 'phonepe://pay',
                            'package' => 'com.phonepe.app',
                            'icon' => 'phonepe',
                            'color' => '#5F259F',
                        ],
                        [
                            'name' => 'Paytm',
                            'code' => 'paytm',
                            'scheme' => 'paytmmp://pay',
                            'package' => 'net.one97.paytm',
                            'icon' => 'paytm',
                            'color' => '#00BAF2',
                        ],
                        [
                            'name' => 'BHIM UPI',
                            'code' => 'bhim',
                            'scheme' => 'upi://pay',
                            'package' => 'in.org.npci.upiapp',
                            'icon' => 'bhim',
                            'color' => '#00695C',
                        ],
                        [
                            'name' => 'Amazon Pay',
                            'code' => 'amazonpay',
                            'scheme' => 'amazonpay://pay',
                            'package' => 'in.amazon.mShop.android.shopping',
                            'icon' => 'amazon',
                            'color' => '#FF9900',
                        ],
                    ],
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching UPI details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create payment intent for tracking
     * Returns UPI details + unique payment reference
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::find($request->order_id);
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Generate unique payment reference (no DB record needed until payment is verified)
            $paymentRef = 'PAY' . strtoupper(uniqid()) . rand(100, 999);

            // Get UPI details
            $upiId = BusinessSetting::where('key', 'upi_id')->first();
            $merchantName = BusinessSetting::where('key', 'upi_merchant_name')->first();

            // Generate UPI URL
            $upiUrl = $this->generateUpiUrl(
                $upiId->value ?? '',
                $merchantName->value ?? 'Store',
                $request->amount,
                $paymentRef,
                "Order #{$order->id}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment intent created successfully',
                'data' => [
                    'payment_ref' => $paymentRef,
                    'order_id' => $order->id,
                    'amount' => (float) $request->amount,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(15)->toIso8601String(),
                    'upi_details' => [
                        'upi_id' => $upiId->value ?? '',
                        'merchant_name' => $merchantName->value ?? 'Store',
                        'upi_url' => $upiUrl,
                    ],
                    'deep_links' => [
                        'gpay' => str_replace('upi://', 'tez://upi/', $upiUrl),
                        'phonepe' => str_replace('upi://pay', 'phonepe://pay', $upiUrl),
                        'paytm' => str_replace('upi://pay', 'paytmmp://pay', $upiUrl),
                        'bhim' => $upiUrl,
                        'amazonpay' => str_replace('upi://pay', 'amazonpay://pay', $upiUrl),
                    ],
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating payment intent: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status (for polling)
     * 
     * @param string $payment_ref
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus($payment_ref)
    {
        try {
            // Check if payment exists in payment_ledgers
            $payment = DB::table('payment_ledgers')
                ->where('transaction_ref', $payment_ref)
                ->first();

            if (!$payment) {
                // Payment not yet verified/created
                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_ref' => $payment_ref,
                        'status' => 'pending',
                        'message' => 'Payment not yet verified'
                    ]
                ], 200);
            }

            $order = Order::find($payment->order_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_ref' => $payment_ref,
                    'status' => $payment->entry_type === 'CREDIT' ? 'complete' : 'refund',
                    'amount' => (float) $payment->amount,
                    'order_id' => $payment->order_id,
                    'order_status' => $order ? $order->order_status : null,
                    'payment_method' => $payment->payment_method,
                    'verified_at' => $payment->created_at,
                    'created_at' => $payment->created_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error checking payment status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel payment
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_ref' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if payment exists in payment_ledgers
            $payment = DB::table('payment_ledgers')
                ->where('transaction_ref', $request->payment_ref)
                ->first();

            if ($payment) {
                // Payment already confirmed, cannot cancel
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a completed payment'
                ], 400);
            }

            // No payment record means it was never confirmed, so cancellation is just acknowledgment
            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
                'data' => [
                    'payment_ref' => $request->payment_ref,
                    'status' => 'cancelled',
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error cancelling payment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment (called by delivery man)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_ref' => 'required|string',
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if already confirmed
            $existing = DB::table('payment_ledgers')
                ->where('transaction_ref', $request->payment_ref)
                ->first();

            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already confirmed'
                ], 400);
            }

            $order = Order::find($request->order_id);

            if (!$order) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Create payment ledger and allocation
            $ledger = PaymentLedger::create([
                'store_id'        => $order->store_id,
                'order_id'        => $order->id,
                'entry_type'      => 'CREDIT',
                'amount'          => $request->amount,
                'payment_method'  => 'online_payment',
                'transaction_ref' => $request->payment_ref,
                'remarks'         => 'Online payment confirmed',
            ]);

            PaymentAllocation::create([
                'payment_ledger_id' => $ledger->id,
                'order_id'          => $order->id,
                'allocated_amount'  => $request->amount,
            ]);

            // Update order
            $newPaidAmount = ($order->paid_amount ?? 0) + $request->amount;
            $totalDue = $order->order_amount + 
                       ($order->total_tax_amount ?? 0) + 
                       ($order->delivery_charge ?? 0) -
                       ($order->coupon_discount_amount ?? 0);

            $order->update([
                'paid_amount'    => $newPaidAmount,
                'payment_status' => ($newPaidAmount >= $totalDue) ? 'paid' : 'partial',
            ]);

            $remaining = max(0, $totalDue - $newPaidAmount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'data' => [
                    'payment_ref' => $request->payment_ref,
                    'status' => 'complete',
                    'amount' => (float) $request->amount,
                    'transaction_id' => $request->transaction_id,
                    'order_id' => $order->id,
                    'order_payment_status' => $order->payment_status,
                    'total_paid' => (float) $newPaidAmount,
                    'total_due' => (float) $totalDue,
                    'remaining' => (float) $remaining,
                    'confirmed_at' => now()->toIso8601String(),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirming payment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate UPI URL for payment
     * 
     * @param string $upiId
     * @param string $merchantName
     * @param float $amount
     * @param string $transactionRef
     * @param string $transactionNote
     * @return string
     */
    private function generateUpiUrl($upiId, $merchantName, $amount, $transactionRef, $transactionNote = '')
    {
        $params = [
            'pa' => $upiId,                    // Payee address (UPI ID)
            'pn' => $merchantName,             // Payee name
            'am' => number_format($amount, 2, '.', ''), // Amount
            'cu' => 'INR',                     // Currency
            'tn' => $transactionNote,          // Transaction note
            'tr' => $transactionRef,           // Transaction reference
        ];

        $queryString = http_build_query($params);
        
        return 'upi://pay?' . $queryString;
    }

    /**
     * @deprecated Pending payments are no longer stored. Use confirmPayment endpoint instead.
     * 
     * Admin: Manually verify a payment (from admin panel)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminVerifyPayment(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint is deprecated. Pending payments are no longer stored. Use the confirmPayment endpoint to record verified payments.',
        ], 410); // 410 Gone
    }
            Log::error('Error verifying payment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history for an order
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderPayments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::find($request->order_id);
            
            // Get payments from payment_allocations joined with payment_ledgers
            $payments = DB::table('payment_allocations as pa')
                ->join('payment_ledgers as pl', 'pa.payment_ledger_id', '=', 'pl.id')
                ->where('pa.order_id', $request->order_id)
                ->orderBy('pl.created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_ref' => $payment->transaction_ref,
                        'amount' => (float) $payment->allocated_amount,
                        'payment_method' => $payment->payment_method,
                        'status' => $payment->entry_type === 'CREDIT' ? 'complete' : 'refund',
                        'payment_date' => $payment->created_at,
                        'created_at' => $payment->created_at,
                    ];
                });

            // Calculate totals
            $totalDue = $order->order_amount + 
                       ($order->total_tax_amount ?? 0) + 
                       ($order->delivery_charge ?? 0) -
                       ($order->coupon_discount_amount ?? 0);

            // Use orders.paid_amount directly
            $totalPaid = $order->paid_amount ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $request->order_id,
                    'payments' => $payments,
                    'summary' => [
                        'total_due' => (float) $totalDue,
                        'total_paid' => (float) $totalPaid,
                        'total_pending' => 0, // No pending concept in new system
                        'remaining' => (float) max(0, $totalDue - $totalPaid),
                        'payment_status' => $order->payment_status,
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching order payments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR code data URL
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateQrCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'order_id' => 'nullable|exists:orders,id',
            'note' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $upiId = BusinessSetting::where('key', 'upi_id')->first();
            $merchantName = BusinessSetting::where('key', 'upi_merchant_name')->first();

            if (empty($upiId->value)) {
                return response()->json([
                    'success' => false,
                    'message' => 'UPI not configured'
                ], 400);
            }

            $transactionRef = 'QR' . strtoupper(uniqid());
            $note = $request->note ?? ($request->order_id ? "Order #{$request->order_id}" : 'Payment');

            $upiUrl = $this->generateUpiUrl(
                $upiId->value,
                $merchantName->value ?? 'Store',
                $request->amount,
                $transactionRef,
                $note
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'upi_url' => $upiUrl,
                    'amount' => (float) $request->amount,
                    'upi_id' => $upiId->value,
                    'merchant_name' => $merchantName->value ?? 'Store',
                    'transaction_ref' => $transactionRef,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}