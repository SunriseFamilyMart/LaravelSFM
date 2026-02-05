<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderChangeLog;
use App\Models\InventoryTransaction;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\GstLedger;
use App\Models\PaymentLedger;
use App\Models\StoreLedger;
use App\Models\AuditLog;

class OrderReturnController extends Controller
{
    public function process(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason'   => 'required|in:damaged,expired,restock',
            'items'    => 'required|array|min:1',
            'items.*.detail_id' => 'required|exists:order_details,id',
            'items.*.qty'       => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $order = Order::with(['details', 'store'])->findOrFail($request->order_id);

            if (!in_array($order->order_status, ['returned', 'partial_delivered'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order status not eligible'
                ], 422);
            }

            $branch = $order->branch;
            $totalTaxable = 0;
            $totalGST = 0;

            // ================= CREATE CREDIT NOTE =================
            $creditNote = CreditNote::create([
                'credit_note_no' => 'CN-' . time(),
                'order_id'       => $order->id,
                'branch'         => $branch,
                'customer_id'    => $order->user_id,
                'reason'         => $request->reason,
                'created_at'     => now()
            ]);

            foreach ($request->items as $item) {

                $detail = OrderDetail::with('product')->findOrFail($item['detail_id']);

                // Prevent double processing
                $changeLog = OrderChangeLog::where('order_detail_id', $detail->id)
                    ->where('processed', 1)
                    ->first();

                if ($changeLog) {
                    throw new \Exception('Item already processed');
                }

                $qty = min($item['qty'], $detail->quantity);
                $price = $detail->price;
                $gstPercent = $detail->tax_percent;

                $taxable = $price * $qty;
                $gstAmount = ($taxable * $gstPercent) / 100;

                $totalTaxable += $taxable;
                $totalGST += $gstAmount;

                // ================= CREDIT NOTE ITEM =================
                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'product_id'     => $detail->product_id,
                    'quantity'       => $qty,
                    'price'          => $price,
                    'gst_percent'    => $gstPercent,
                ]);

                // ================= INVENTORY =================
                if ($request->reason === 'restock') {
                    InventoryTransaction::create([
                        'product_id'   => $detail->product_id,
                        'branch'       => $branch,
                        'type'         => 'IN',
                        'quantity'     => $qty,
                        'unit_price'   => $price,
                        'total_value'  => $taxable,
                        'reference_type' => 'credit_note',
                        'reference_id' => $creditNote->id,
                        'order_id'     => $order->id,
                        'created_at'   => now(),
                    ]);
                } else {
                    InventoryTransaction::create([
                        'product_id'   => $detail->product_id,
                        'branch'       => $branch,
                        'type'         => 'OUT',
                        'quantity'     => $qty,
                        'unit_price'   => $price,
                        'total_value'  => $taxable,
                        'reference_type' => 'damage',
                        'reference_id' => $creditNote->id,
                        'order_id'     => $order->id,
                        'created_at'   => now(),
                    ]);
                }

                // ================= ORDER CHANGE LOG =================
                OrderChangeLog::create([
                    'order_id'        => $order->id,
                    'order_detail_id' => $detail->id,
                    'old_quantity'    => $detail->quantity,
                    'new_quantity'    => $detail->quantity - $qty,
                    'old_price'       => $detail->price,
                    'new_price'       => $detail->price,
                    'processed'       => 1,
                    'processed_reason'=> $request->reason,
                    'credit_note_id'  => $creditNote->id,
                    'processed_at'    => now(),
                ]);
            }

            // ================= UPDATE CREDIT NOTE TOTAL =================
            $creditNote->update([
                'taxable_amount' => $totalTaxable,
                'gst_amount'     => $totalGST,
                'total_amount'   => $totalTaxable + $totalGST,
            ]);

            // ================= GST REVERSAL =================
            GstLedger::create([
                'branch'          => $branch,
                'type'            => 'OUTPUT',
                'taxable_amount'  => -$totalTaxable,
                'gst_amount'      => -$totalGST,
                'reference_type'  => 'credit_note',
                'reference_id'    => $creditNote->id,
                'created_at'      => now(),
            ]);

            // ================= PAYMENT REVERSAL =================
            if ($order->paid_amount > 0) {
                PaymentLedger::create([
                    'store_id'      => $order->store_id,
                    'order_id'      => $order->id,
                    'entry_type'    => 'CREDIT',
                    'amount'        => $creditNote->total_amount,
                    'payment_method'=> 'credit_note',
                    'remarks'       => 'Return credit note',
                    'created_at'    => now(),
                ]);
            }

            // ================= STORE LEDGER =================
            StoreLedger::create([
                'store_id'       => $order->store_id,
                'reference_type' => 'credit_note',
                'reference_id'   => $creditNote->id,
                'credit'         => $creditNote->total_amount,
                'remarks'        => 'Return adjustment',
                'created_at'     => now(),
            ]);

            // ================= AUDIT LOG =================
            AuditLog::create([
                'user_id'    => auth()->id(),
                'branch'     => $branch,
                'action'     => 'RETURN_PROCESSED',
                'table_name' => 'credit_notes',
                'record_id'  => $creditNote->id,
                'new_values' => json_encode($request->all()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'credit_note_id' => $creditNote->id
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
