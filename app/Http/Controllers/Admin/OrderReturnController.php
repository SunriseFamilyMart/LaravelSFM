<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Model\Order;
use App\Model\OrderDetail;
use App\Models\OrderChangeLog;

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
        /** ---------------- LOCK ORDER ---------------- */
        $order = Order::with(['details', 'branch'])
            ->lockForUpdate()
            ->findOrFail($request->order_id);

        if (!in_array($order->order_status, ['returned', 'partial_delivered'])) {
            throw new \Exception('Order not eligible for return');
        }

        if (!$order->branch) {
            throw new \Exception('Branch not linked to order');
        }

        /** ---------------- CREATE CREDIT NOTE ---------------- */
        $creditNote = CreditNote::create([
            'credit_note_no' => 'CN-' . \Illuminate\Support\Str::ulid(),
            'order_id'       => $order->id,
            'branch'         => $order->branch->name,
            'customer_id'    => $order->user_id,
            'reason'         => $request->reason,
            'taxable_amount' => 0,
            'gst_amount'     => 0,
            'total_amount'   => 0,
        ]);

        $totalTaxable = 0;
        $totalGST = 0;

        /** ---------------- PROCESS ITEMS ---------------- */
        foreach ($request->items as $item) {

            $detail = OrderDetail::lockForUpdate()->findOrFail($item['detail_id']);

            if ($detail->order_id !== $order->id) {
                throw new \Exception('Invalid item selected');
            }

            // Already returned qty
            $returnedQty = CreditNoteItem::where('order_detail_id', $detail->id)
                ->sum('quantity');

            $availableQty = $detail->quantity - $returnedQty;

            if ($item['qty'] > $availableQty) {
                throw new \Exception('Return quantity exceeds available quantity');
            }

            $returnQty = $item['qty'];

            /** -------- PRICE & GST -------- */
            $price = $detail->price;
            $gstPercent = $detail->gst_percent ?? 0;

            $taxable = $price * $returnQty;
            $gstAmount = ($taxable * $gstPercent) / 100;

            $totalTaxable += $taxable;
            $totalGST += $gstAmount;

            /** ---------------- CREDIT NOTE ITEM ---------------- */
            CreditNoteItem::create([
                'credit_note_id'  => $creditNote->id,
                'order_detail_id' => $detail->id,
                'product_id'      => $detail->product_id,
                'quantity'        => $returnQty,
                'price'           => $price,
                'gst_percent'     => $gstPercent,
                'gst_amount'      => $gstAmount,
            ]);

            /** ---------------- UPDATE ORDER DETAIL QTY ---------------- */
          //  $detail->quantity -= $returnQty;
          //  $detail->save();

            /** ---------------- INVENTORY ---------------- */
            InventoryTransaction::create([
                'product_id'     => $detail->product_id,
                'branch'         => $order->branch->name,
                'type'           => $request->reason === 'restock' ? 'IN' : 'SCRAP',
                'quantity'       => $returnQty,
                'unit_price'     => $price,
                'total_value'    => $taxable,
                'reference_type' => 'credit_note',
                'reference_id'   => $creditNote->id,
                'order_id'       => $order->id,
            ]);

            /** ---------------- CHANGE LOG ---------------- */
            OrderChangeLog::create([
                'order_id'        => $order->id,
                'order_detail_id' => $detail->id,
                'old_quantity'    => $detail->quantity + $returnQty,
                'new_quantity'    => $detail->quantity,
                'processed'       => 1,
                'processed_reason'=> $request->reason,
                'credit_note_id'  => $creditNote->id,
                'processed_at'    => now(),
            ]);
        }

        /** ---------------- UPDATE CREDIT NOTE TOTAL ---------------- */
        $creditNote->update([
            'taxable_amount' => $totalTaxable,
            'gst_amount'     => $totalGST,
            'total_amount'   => $totalTaxable + $totalGST,
        ]);

        /** ---------------- GST REVERSAL ---------------- */
        if ($totalGST > 0) {
            GstLedger::create([
                'branch'         => $order->branch->name,
                'type'           => 'OUTPUT',
                'taxable_amount' => -$totalTaxable,
                'gst_amount'     => -$totalGST,
                'reference_type' => 'credit_note',
                'reference_id'   => $creditNote->id,
            ]);
        }

        /** ---------------- PAYMENT ADJUSTMENT ---------------- */
        if ($order->paid_amount > 0) {
            $adjust = min($order->paid_amount, $creditNote->total_amount);

            PaymentLedger::create([
                'store_id'       => $order->store_id,
                'order_id'       => $order->id,
                'entry_type'     => 'DEBIT',
                'amount'         => $adjust,
                'payment_method' => 'credit_note',
                'remarks'        => 'Return credit note',
            ]);

            $order->paid_amount -= $adjust;
            $order->payment_status = $order->paid_amount > 0 ? 'partial' : 'unpaid';
        }

        /** ---------------- ORDER STATUS ---------------- */
       $remainingQty = $order->details->sum(function ($detail) {
    $returned = CreditNoteItem::where('order_detail_id', $detail->id)->sum('quantity');
    return $detail->quantity - $returned;
});
        $order->order_status = $remainingQty === 0
            ? 'returned'
            : 'partial_delivered';

        $order->save();

        /** ---------------- AUDIT ---------------- */
        AuditLog::create([
            'user_id'    => auth()->id(),
            'branch'     => $order->branch->name,
            'action'     => 'ORDER_RETURN',
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
            'order_id' => $request->order_id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

}
