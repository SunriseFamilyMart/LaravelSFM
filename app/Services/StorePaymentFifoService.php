<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorePaymentFifoService
{
    /**
     * Apply a store payment using FIFO (oldest orders first)
     *
     * @param int $storeId
     * @param float $amount  Total amount PAID by store
     * @param string $method Payment method (cash / upi / bank / cheque)
     * @param string|null $txnId Transaction reference
     * @param int $userId Logged-in user (deliveryman / admin)
     */
    public static function apply(
        int $storeId,
        float $amount,
        string $method,
        ?string $txnId,
        int $userId
    ) {
        DB::beginTransaction();

        try {
            Log::info('FIFO store payment started', compact(
                'storeId', 'amount', 'method', 'txnId', 'userId'
            ));

            /* =====================================================
             | 1️⃣ CREATE ONE PAYMENT LEDGER (ACTUAL PAYMENT EVENT)
             ===================================================== */
            $paymentLedgerId = DB::table('payment_ledgers')->insertGetId([
                'store_id'        => $storeId,
                'entry_type'      => 'CREDIT', // Store paid us
                'amount'          => $amount,  // FULL payment amount
                'payment_method'  => $method,
                'transaction_ref' => $txnId,
                'remarks'         => 'Store payment (FIFO allocation)',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            Log::info('Payment ledger created', [
                'payment_ledger_id' => $paymentLedgerId,
                'amount' => $amount
            ]);

            /* =====================================================
             | 2️⃣ FETCH OLD ORDERS (FIFO)
             ===================================================== */
            $orders = Order::where('store_id', $storeId)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $remainingAmount = $amount;

            foreach ($orders as $order) {

                if ($remainingAmount <= 0) {
                    break;
                }

                $orderTotal  = $order->order_amount + $order->total_tax_amount;
                $alreadyPaid = $order->paid_amount ?? 0;
                $due         = $orderTotal - $alreadyPaid;

                if ($due <= 0) {
                    continue;
                }

                $payNow = min($remainingAmount, $due);

                /* =====================================================
                 | 3️⃣ PAYMENT ALLOCATION (LINK PAYMENT → ORDER)
                 ===================================================== */
                DB::table('payment_allocations')->insert([
                    'payment_ledger_id' => $paymentLedgerId,
                    'order_id'          => $order->id,
                    'allocated_amount'  => $payNow,
                    'created_at'        => now(),
                ]);

                /* =====================================================
                 | 4️⃣ UPDATE ORDER PAYMENT STATUS
                 ===================================================== */
                $order->paid_amount += $payNow;
                $order->payment_status = ($order->paid_amount >= $orderTotal)
                    ? 'paid'
                    : 'partial';
                $order->save();

                /* =====================================================
                 | 5️⃣ STORE LEDGER (RECEIVABLE REDUCTION)
                 ===================================================== */
                DB::table('store_ledgers')->insert([
                    'store_id'       => $storeId,
                    'reference_type' => 'payment',
                    'reference_id'   => $paymentLedgerId,
                    'debit'          => 0,
                    'credit'         => $payNow,
                    'balance_type'   => 'receivable',
                    'remarks'        => 'FIFO payment applied to order #' . $order->id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                /* =====================================================
                 | 6️⃣ AUDIT LOG
                 ===================================================== */
                DB::table('audit_logs')->insert([
                    'user_id'    => $userId,
                    'branch'     => $order->branch,
                    'action'     => 'fifo_payment_allocated',
                    'table_name' => 'orders',
                    'record_id'  => $order->id,
                    'new_values' => json_encode([
                        'allocated_amount'  => $payNow,
                        'payment_ledger_id' => $paymentLedgerId,
                        'payment_method'    => $method
                    ]),
                    'created_at' => now(),
                ]);

                Log::info('FIFO allocation done', [
                    'order_id' => $order->id,
                    'allocated_amount' => $payNow,
                ]);

                $remainingAmount -= $payNow;
            }

            /* =====================================================
             | 7️⃣ COMMIT
             ===================================================== */
            DB::commit();

            Log::info('FIFO store payment completed', [
                'payment_ledger_id' => $paymentLedgerId
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('FIFO store payment failed', [
                'error' => $e->getMessage(),
                'store_id' => $storeId
            ]);

            throw $e;
        }
    }
}
