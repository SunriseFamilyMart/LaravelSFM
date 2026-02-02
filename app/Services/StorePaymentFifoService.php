namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

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
            $orders = Order::where('store_id', $storeId)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                if ($amount <= 0) break;

                $orderTotal = $order->order_amount + $order->total_tax_amount;
                $paid = OrderPayment::where('order_id', $order->id)
                    ->where('payment_status', 'complete')
                    ->sum('amount');

                $due = $orderTotal - $paid;
                if ($due <= 0) continue;

                $payNow = min($amount, $due);

                // 1️⃣ Order payment
                OrderPayment::create([
                    'order_id' => $order->id,
                    'store_id' => $storeId,
                    'amount' => $payNow,
                    'payment_method' => $method,
                    'transaction_id' => $txnId,
                    'payment_status' => 'complete'
                ]);

                // 2️⃣ Update order
                $order->payment_status = ($payNow == $due) ? 'paid' : 'partial';
                $order->save();

                // 3️⃣ Store ledger
                $lastBalance = DB::table('store_ledgers')
                    ->where('store_id', $storeId)
                    ->latest()
                    ->value('balance_after') ?? 0;

                DB::table('store_ledgers')->insert([
                    'store_id' => $storeId,
                    'order_id' => $order->id,
                    'entry_type' => 'credit',
                    'amount' => $payNow,
                    'balance_after' => $lastBalance + $payNow,
                    'reference_type' => 'payment',
                    'reference_id' => $order->id,
                    'created_at' => now()
                ]);

                // 4️⃣ Audit
                DB::table('audit_logs')->insert([
                    'user_id' => $userId,
                    'branch' => $order->branch_id,
                    'action' => 'fifo_payment_applied',
                    'table_name' => 'orders',
                    'record_id' => $order->id,
                    'new_values' => json_encode([
                        'paid' => $payNow,
                        'method' => $method
                    ]),
                    'created_at' => now()
                ]);

                $amount -= $payNow;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
