<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\PaymentLedger;
use App\Models\PaymentAllocation;
use App\Models\AuditLog;

class PaymentFifoService
{
    public static function applyPaymentFIFO(
        int $storeId,
        float $amount,
        string $paymentMethod,
        ?string $txnRef = null,
        ?int $userId = null,
        ?int $branchId = null
    ): PaymentLedger {

        return DB::transaction(function () use (
            $storeId,
            $amount,
            $paymentMethod,
            $txnRef,
            $userId,
            $branchId
        ) {

            /** 1️⃣ Create PAYMENT LEDGER (CREDIT) */
            $ledger = PaymentLedger::create([
                'store_id'        => $storeId,
                'entry_type'      => 'CREDIT',
                'amount'          => $amount,
                'payment_method'  => $paymentMethod,
                'transaction_ref' => $txnRef,
                'remarks'         => 'Store payment received',
            ]);

            /** 2️⃣ Fetch oldest unpaid orders (FIFO) */
            $orders = Order::where('store_id', $storeId)
                ->whereRaw('(order_amount - paid_amount) > 0')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $remaining = $amount;

            foreach ($orders as $order) {

                if ($remaining <= 0) {
                    break;
                }

                $due = $order->order_amount - $order->paid_amount;

                $allocate = min($due, $remaining);

                /** 3️⃣ Allocate payment */
                PaymentAllocation::create([
                    'payment_ledger_id' => $ledger->id,
                    'order_id'          => $order->id,
                    'allocated_amount'  => $allocate,
                ]);

                /** 4️⃣ Update order paid amount */
                $order->paid_amount += $allocate;
                $order->payment_status = ($order->paid_amount >= $order->order_amount)
                    ? 'paid'
                    : 'partial';

                $order->save();

                $remaining -= $allocate;
            }

            /** 5️⃣ Audit log */
            AuditLog::create([
                'user_id'    => $userId,
                'branch'     => $branchId,
                'action'     => 'STORE_PAYMENT_FIFO_APPLIED',
                'table_name' => 'payment_ledgers',
                'record_id'  => $ledger->id,
                'new_values' => json_encode([
                    'store_id' => $storeId,
                    'amount'  => $amount,
                    'method'  => $paymentMethod
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $ledger;
        });
    }
}
