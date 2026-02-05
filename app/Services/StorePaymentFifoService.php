<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorePaymentFifoService
{
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

            /* ================= FETCH OLD ORDERS (FIFO) ================= */
            $orders = Order::where('store_id', $storeId)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {

                if ($amount <= 0) break;

                $orderTotal = $order->order_amount + $order->total_tax_amount;
                $alreadyPaid = $order->paid_amount ?? 0;

                $due = $orderTotal - $alreadyPaid;
                if ($due <= 0) continue;

                $payNow = min($amount, $due);

                /* ================= PAYMENT LEDGER ================= */
                $paymentLedgerId = DB::table('payment_ledgers')->insertGetId([
                    'store_id' => $storeId,
                    'order_id' => $order->id,
                    'entry_type' => 'CREDIT',
                    'amount' => $payNow,
                    'payment_method' => $method,
                    'transaction_ref' => $txnId,
                    'remarks' => 'FIFO store payment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /* ================= PAYMENT ALLOCATION ================= */
                DB::table('payment_allocations')->insert([
                    'payment_ledger_id' => $paymentLedgerId,
                    'order_id' => $order->id,
                    'allocated_amount' => $payNow,
                    'created_at' => now(),
                ]);

                /* ================= UPDATE ORDER ================= */
                $order->paid_amount += $payNow;
                $order->payment_status = ($order->paid_amount >= $orderTotal)
                    ? 'paid'
                    : 'partial';
                $order->save();

                /* ================= STORE LEDGER ================= */
                DB::table('store_ledgers')->insert([
                    'store_id' => $storeId,
                    'reference_type' => 'payment',
                    'reference_id' => $order->id,
                    'debit' => 0,
                    'credit' => $payNow,
                    'balance_type' => 'receivable',
                    'remarks' => 'FIFO payment applied',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                /* ================= AUDIT LOG ================= */
                DB::table('audit_logs')->insert([
                    'user_id' => $userId,
                    'branch' => $order->branch,
                    'action' => 'fifo_payment_applied',
                    'table_name' => 'orders',
                    'record_id' => $order->id,
                    'new_values' => json_encode([
                        'paid_amount' => $payNow,
                        'payment_method' => $method
                    ]),
                    'created_at' => now(),
                ]);

                $amount -= $payNow;
            }

            DB::commit();

            Log::info('FIFO store payment completed');

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('FIFO store payment failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
