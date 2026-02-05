<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\OrderChangeLog;

use App\Models\InventoryTransaction;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\GstLedger;
use App\Models\PaymentLedger;
use App\Models\StoreLedger;
use App\Models\AuditLog;
use App\Model\Branch;

class OrderReturnController extends Controller
{
    public function process(Request $request)
    {
        /* ================= JSON SAFETY ================= */
        if ($request->isJson()) {
            $request->merge(json_decode($request->getContent(), true));
        }

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason'   => 'required|in:damaged,expired,restock',
            'items'    => 'required|array|min:1',
            'items.*.detail_id' => 'required|exists:order_details,id',
            'items.*.qty'       => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            /* ================= LOCK ORDER ================= */
            $order = Order::with(['details', 'branch'])
                ->lockForUpdate()
                ->findOrFail($request->order_id);

            if (!in_array($order->order_status, ['returned', 'partial_delivered'])) {
                throw new \Exception('Order not eligible for return processing');
            }

            if (!$order->branch) {
                throw new \Exception('Branch not linked to order');
            }

            $branchName = $order->branch->name;

            /* ================= CREATE CREDIT NOTE ================= */
            $creditNote = CreditNote::create([
                'credit_note_no' => 'CN-' . now()->format('YmdHis') . rand(100, 999),
                'order_id'       => $order->id,
                'branch'         => $branchName,
                'customer_id'    => $order->user_id,
                'reason'         => $request->reason,
                'taxable_amount' => 0,
                'gst_amount'     => 0,
                'total_amount'   => 0,
            ]);

            $totalTaxable = 0;
            $totalGST = 0;

            /* ================= PROCESS EACH ITEM ================= */
            foreach ($request->items as $item) {

                $detail = OrderDetail::lockForUpdate()->findOrFail($item['detail_id']);

                // Prevent double return
                if (OrderChangeLog::where('order_detail_id', $detail->id)
                    ->where('processed', 1)
                    ->exists()) {
                    throw new \Exception("Item already processed (OrderDetail ID {$detail->id})");
                }

                $returnQty = min($item['qty'], $detail->quantity);
                if ($returnQty <= 0) {
                    continue;
                }

                $price = $detail->price;

                /* -------- GST % DERIVATION -------- */
                $gstPercent = ($detail->tax_amount > 0 && $detail->quantity > 0)
                    ? round(($detail->tax_amount / ($detail->price * $detail->quantity)) * 100, 2)
                    : 0;

                $taxable = $price * $returnQty;
                $gstAmount = ($taxable * $gstPercent) / 100;

                $totalTaxable += $taxable;
                $totalGST += $gstAmount;

                /* ================= CREDIT NOTE ITEM ================= */
                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'product_id'     => $detail->product_id,
                    'quantity'       => $returnQty,
                    'price'          => $price,
                    'gst_percent'    => $gstPercent,
                ]);

                /* ================= INVENTORY TRANSACTION ================= */
                InventoryTransaction::create([
                    'product_id'     => $detail->product_id,
                    'branch'         => $branchName,
                    'type'           => $request->reason === 'restock' ? 'IN' : 'OUT',
                    'quantity'       => $returnQty,
                    'unit_price'     => $price,
                    'total_value'    => $taxable,
                    'remaining_qty'  => $request->reason === 'restock' ? $returnQty : 0,
                    'reference_type' => 'credit_note',
                    'reference_id'   => $creditNote->id,
                    'order_id'       => $order->id,
                    'created_at'     => now(),
                ]);

                /* ================= ORDER CHANGE LOG ================= */
                OrderChangeLog::create([
                    'order_id'        => $order->id,
                    'order_detail_id' => $detail->id,
                    'old_quantity'    => $detail->quantity,
                    'new_quantity'    => $detail->quantity - $returnQty,
                    'old_price'       => $detail->price,
                    'new_price'       => $detail->price,
                    'processed'       => 1,
                    'processed_reason'=> $request->reason,
                    'credit_note_id'  => $creditNote->id,
                    'processed_at'    => now(),
                ]);
            }

            /* ================= UPDATE CREDIT NOTE TOTAL ================= */
            $creditNote->update([
                'taxable_amount' => $totalTaxable,
                'gst_amount'     => $totalGST,
                'total_amount'   => $totalTaxable + $totalGST,
            ]);

            /* ================= GST REVERSAL ================= */
            if ($totalGST > 0) {
                GstLedger::create([
                    'branch'         => $branchName,
                    'type'           => 'OUTPUT',
                    'taxable_amount' => -$totalTaxable,
                    'gst_amount'     => -$totalGST,
                    'reference_type' => 'credit_note',
                    'reference_id'   => $creditNote->id,
                ]);
            }

            /* ================= PAYMENT REVERSAL ================= */
            if ($order->paid_amount > 0) {
                $reverseAmount = min($order->paid_amount, $creditNote->total_amount);

                PaymentLedger::create([
                    'store_id'       => $order->store_id,
                    'order_id'       => $order->id,
                    'entry_type'     => 'DEBIT',
                    'amount'         => $reverseAmount,
                    'payment_method' => 'credit_note',
                    'remarks'        => 'Order return credit note',
                ]);

                $order->paid_amount -= $reverseAmount;
                $order->payment_status = $order->paid_amount > 0 ? 'partial' : 'unpaid';
                $order->save();
            }

            /* ================= STORE LEDGER ================= */
            StoreLedger::create([
                'store_id'       => $order->store_id,
                'reference_type' => 'credit_note',
                'reference_id'   => $creditNote->id,
                'credit'         => $creditNote->total_amount,
                'remarks'        => 'Return credit note generated',
            ]);

            /* ================= AUDIT LOG ================= */
            AuditLog::create([
                'user_id'    => auth()->id(),
                'branch'     => $branchName,
                'action'     => 'ORDER_RETURN_PROCESSED',
                'table_name' => 'credit_notes',
                'record_id'  => $creditNote->id,
                'new_values' => json_encode($request->all()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'credit_note_id' => $creditNote->id,
                'message' => 'Return processed successfully'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('ORDER RETURN FAILED', [
                'order_id' => $request->order_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
