<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\BusinessSetting;
use App\Model\DeliveryMan;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\OrderEditLog;
use App\Model\Product;
use App\Models\OrderPayment;


use App\Models\Supplier;
use App\Models\DeliveryChargeByArea;
use App\Models\OfflinePayment;
use App\Models\OrderArea;
use App\Models\OrderPartialPayment;
use App\Traits\HelperTrait;
use App\User;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function App\CentralLogics\translate;
use Illuminate\Support\Str;
use App\Models\DeliveryTrip;


class OrderController extends Controller
{
    use HelperTrait;
    public function __construct(
        private Branch $branch,
        private BusinessSetting $business_setting,
        private DeliveryMan $delivery_man,
        private Order $order,
        private OrderDetail $order_detail,
        private Product $product,
        private User $user,
        private OrderArea $orderArea
    ) {
    }

    /**
     * @param Request $request
     * @param $status
     * @return Factory|View|Application
     */
  public function list(Request $request, $status): View|Factory|Application
{
    $queryParam = [];
    $search = $request['search'] ?? null;

    $branches = $this->branch->all();
    $branchId = $request['branch_id'] ?? 'all';
    $deliveryManId = $request['delivery_man_id'] ?? 'all';
  $deliveryMen = $this->delivery_man->all();

    $paymentMethod = $request['payment_method'] ?? 'all';
    $startDate = $request['start_date'] ?? null;
    $endDate = $request['end_date'] ?? null;


    // Returned sub-filter (read-only): all | partial | full
    $returnType = $request->get('return_type', 'all');

    // Mark unchecked orders as checked
    $this->order->where(['checked' => 0])->update(['checked' => 1]);

    $query = $this->order->with(['customer', 'branch', 'delivery_man', 'payments'])

        // Branch filter
        ->when(($branchId && $branchId != 'all'), function ($query) use ($branchId) {
            return $query->where('branch_id', $branchId);
        })

        // Date range filter
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            return $query->whereDate('created_at', '>=', $startDate)
                         ->whereDate('created_at', '<=', $endDate);
        })

        // Delivery Man filter
        ->when($deliveryManId && $deliveryManId != 'all', function ($query) use ($deliveryManId) {
            return $query->where('delivery_man_id', $deliveryManId);
        })

        // Payment Method filter
        ->when($paymentMethod && $paymentMethod != 'all', function ($query) use ($paymentMethod) {
            return $query->whereHas('payments', function ($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            });
        });

    // Order status filter
    if ($status != 'all') {
        if ($status === 'returned') {
            // Include: (A) orders marked as returned AND (B) orders that have item-wise return/edit logs
            $logsOrderIds = OrderEditLog::query()->select('order_id')->distinct();
            $query->where(function ($q) use ($logsOrderIds) {
                $q->where('order_status', 'returned')
                  ->orWhereIn('id', $logsOrderIds);
            });
        } else {
            $query->where(['order_status' => $status]);
        }
    }

    // Returned partial/full filtering (based on order_edit_logs)
    if ($status === 'returned' && in_array($returnType, ['partial', 'full'])) {
        $partialOrderIds = OrderEditLog::query()
            ->select('order_id')
            ->where('new_quantity', '>', 0)
            ->distinct();

        if ($returnType === 'partial') {
            $query->whereIn('id', $partialOrderIds);
        } else {
            // Option A: Full return includes returned orders WITHOUT logs (treated as full)
            $query->whereNotIn('id', $partialOrderIds);
        }
    }


    // Search filter
    if ($request->has('search')) {
        $key = explode(' ', $request['search']);
        $query->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                  ->orWhere('order_status', 'like', "%{$value}%")
                  ->orWhere('payment_status', 'like', "{$value}%");
            }
        });
        $queryParam['search'] = $search;
    }

    // Prepare query params for pagination links
    $queryParam = array_merge($queryParam, [
        'branch_id' => $branchId,
        'delivery_man_id' => $deliveryManId,
        'payment_method' => $paymentMethod,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'return_type' => $returnType,
    ]);

    $orders = $query->notPos()
                    ->orderBy('id', 'desc')
                    ->paginate(Helpers::getPagination())
                    ->appends($queryParam);

    // Returned sub-tab counts (Option A: orders with no logs are treated as FULL return)
    $returnedTypeCounts = ['all' => 0, 'partial' => 0, 'full' => 0];
    $returnMeta = [];

    if ($status === 'returned') {
        // Base returned query with the same filters (excluding search + return_type)
        $baseReturned = $this->order->notPos()
            ->when(($branchId && $branchId != 'all'), function ($q) use ($branchId) { return $q->where('branch_id', $branchId); })
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) { return $q->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate); })
            ->when($deliveryManId && $deliveryManId != 'all', function ($q) use ($deliveryManId) { return $q->where('delivery_man_id', $deliveryManId); })
            ->when($paymentMethod && $paymentMethod != 'all', function ($q) use ($paymentMethod) {
                return $q->whereHas('payments', function ($p) use ($paymentMethod) {
                    $p->where('payment_method', $paymentMethod);
                });
            })
            ->where('order_status', 'returned');

        $returnedTypeCounts['all'] = (clone $baseReturned)->count();

        $partialOrderIds = OrderEditLog::query()
            ->select('order_id')
            ->where('new_quantity', '>', 0)
            ->distinct();

        $returnedTypeCounts['partial'] = (clone $baseReturned)->whereIn('id', $partialOrderIds)->count();
        $returnedTypeCounts['full'] = (clone $baseReturned)->whereNotIn('id', $partialOrderIds)->count();

        // Return summary for only current page orders (fast)
        $orderIds = $orders->pluck('id')->toArray();

        if (!empty($orderIds)) {
            $logsByOrder = OrderEditLog::query()
                ->whereIn('order_id', $orderIds)
                ->orderBy('id')
                ->get()
                ->groupBy('order_id');

            // order_details fallback counts for "no logs" orders
            $detailsAgg = DB::table('order_details')
                ->select('order_id', DB::raw('COUNT(*) as items_count'), DB::raw('SUM(quantity) as total_qty'))
                ->whereIn('order_id', $orderIds)
                ->groupBy('order_id')
                ->get()
                ->keyBy('order_id');

            foreach ($orderIds as $oid) {
                $logs = $logsByOrder->get($oid, collect());

                if ($logs->isEmpty()) {
                    $itemsCount = (int) optional($detailsAgg->get($oid))->items_count;
                    $totalQty = (int) optional($detailsAgg->get($oid))->total_qty;

                    $returnMeta[$oid] = [
                        'type' => 'full',
                        'items_count' => $itemsCount,
                        'total_return_qty' => $totalQty,
                        'has_logs' => false,
                    ];
                    continue;
                }

                $isPartial = $logs->contains(function ($l) { return (int)$l->new_quantity > 0; });

                // item-wise: (first old_quantity - last new_quantity)
                $byItem = $logs->groupBy('order_detail_id');
                $itemsCount = $byItem->count();
                $totalReturnQty = 0;

                foreach ($byItem as $gid => $group) {
                    $sorted = $group->sortBy('id')->values();
                    $firstOld = (int) optional($sorted->first())->old_quantity;
                    $lastNew = (int) optional($sorted->last())->new_quantity;
                    $totalReturnQty += max(0, $firstOld - $lastNew);
                }

                $returnMeta[$oid] = [
                    'type' => $isPartial ? 'partial' : 'full',
                    'items_count' => (int)$itemsCount,
                    'total_return_qty' => (int)$totalReturnQty,
                    'has_logs' => true,
                ];
            }
        }
    }


    // Count orders by status (keeping old functionality)
    $countData = [];
    $orderStatuses = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'canceled', 'returned', 'failed'];

    foreach ($orderStatuses as $orderStatus) {
        $q = $this->order->notPos();

        if ($orderStatus === 'returned') {
            // Returned count should include: orders with status=returned OR orders with item-wise return/edit logs
            $logsOrderIds = OrderEditLog::query()->select('order_id')->distinct();
            $q->where(function ($x) use ($logsOrderIds) {
                $x->where('order_status', 'returned')
                  ->orWhereIn('id', $logsOrderIds);
            });
        } else {
            $q->where('order_status', $orderStatus);
        }

        $countData[$orderStatus] = $q
            ->when(!is_null($branchId) && $branchId != 'all', function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when(!is_null($startDate) && !is_null($endDate), function ($query) use ($startDate, $endDate) {
                return $query->whereDate('created_at', '>=', $startDate)
                             ->whereDate('created_at', '<=', $endDate);
            })
            ->count();
    }

return view('admin-views.order.list', compact(
        'orders', 'status', 'search', 'branches', 'branchId', 'deliveryManId',
        'paymentMethod', 'startDate', 'endDate', 'countData','deliveryMen', 'returnedTypeCounts', 'returnMeta', 'returnType'
    ));
}

    /**
     * Read-only: return item-wise partial return summary + full edit logs for an order.
     * Used by Admin Returned Orders list page (modal).
     */
    public function returnedLogs($order_id): JsonResponse
    {
        // Fetch logs first
        $logs = OrderEditLog::with(['deliveryMan', 'orderDetail.product'])
            ->where('order_id', $order_id)
            ->orderBy('id')
            ->get();

        // Option A: if order is returned but has no logs, treat as FULL return and build from order_details
        if ($logs->isEmpty()) {
            $details = OrderDetail::with('product')
                ->where('order_id', $order_id)
                ->get();

            $items = $details->map(function ($d) {
                return [
                    'order_detail_id' => $d->id,
                    'product_name' => optional($d->product)->name ?? (json_decode($d->product_details, true)['name'] ?? ('Item #' . $d->product_id)),
                    'old_quantity' => (int) $d->quantity,
                    'new_quantity' => 0,
                    'returned_qty' => (int) $d->quantity,
                    'reason' => translate('Returned (no edit logs)'),
                    'photo' => null,
                    'last_updated_at' => null,
                    'history' => [],
                ];
            })->values();

            return response()->json([
                'order_id' => (int) $order_id,
                'type' => 'full',
                'summary' => [
                    'items_count' => $items->count(),
                    'total_return_qty' => (int) $items->sum('returned_qty'),
                ],
                'items' => $items,
            ]);
        }

        $isPartial = $logs->contains(function ($l) { return (int)$l->new_quantity > 0; });

        $items = $logs->groupBy('order_detail_id')->map(function ($group) {
            $sorted = $group->sortBy('id')->values();

            $first = $sorted->first();
            $last = $sorted->last();

            $firstOld = (int) optional($first)->old_quantity;
            $lastNew = (int) optional($last)->new_quantity;

            $returnedQty = max(0, $firstOld - $lastNew);

            $od = optional($last)->orderDetail;
            $pName = optional(optional($od)->product)->name;
            if (!$pName && $od && $od->product_details) {
                $pName = json_decode($od->product_details, true)['name'] ?? null;
            }

            return [
                'order_detail_id' => (int) $group->first()->order_detail_id,
                'product_name' => $pName ?? ('Item #' . (optional($od)->product_id ?? '')),
                'old_quantity' => $firstOld,
                'new_quantity' => $lastNew,
                'returned_qty' => $returnedQty,
                'reason' => (string) optional($last)->reason,
                'photo' => optional($last)->photo,
                'last_updated_at' => optional($last)->updated_at ? optional($last)->updated_at->toDateTimeString() : null,
                'history' => $sorted->map(function ($l) {
                    return [
                        'id' => (int) $l->id,
                        'old_quantity' => (int) $l->old_quantity,
                        'new_quantity' => (int) $l->new_quantity,
                        'reason' => (string) $l->reason,
                        'photo' => $l->photo,
                        'delivery_man' => $l->deliveryMan ? trim($l->deliveryMan->f_name.' '.$l->deliveryMan->l_name) : null,
                        'created_at' => $l->created_at ? $l->created_at->toDateTimeString() : null,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'order_id' => (int) $order_id,
            'type' => $isPartial ? 'partial' : 'full',
            'summary' => [
                'items_count' => $items->count(),
                'total_return_qty' => (int) $items->sum('returned_qty'),
            ],
            'items' => $items,
        ]);
    }



public function orderManagement(Request $request)
{
    $supplierId = $request->supplier;
    $productId  = $request->product;
    $status     = $request->status;
    $limit      = $request->get('limit', 10);

    // ---------------- ORDERS ----------------
    $orders = Order::with([
            'store',
            'details.product.supplier',
            'details.editLogs',
            'payments' => function ($q) {
                $q->where('payment_status', 'complete');
            }
        ])
        ->when($status, fn ($q) => $q->where('order_status', $status))
        ->whereHas('details', function ($q) use ($productId, $supplierId) {
            if ($productId) $q->where('product_id', $productId);
            if ($supplierId) {
                $q->whereHas('product', fn($p) => $p->where('supplier_id', $supplierId));
            }
        })
        ->latest()
        ->paginate($limit);

    $orderCollection = collect($orders->items());

    // ---------------- PAGE LEVEL SUMMARY ----------------
    $totalPaid = $orderCollection->sum(fn($order) => $order->payments->sum('amount'));
    $totalPurchase = $orderCollection->sum(fn($order) => $order->order_amount + ($order->total_tax_amount ?? 0));

    $summary = [
        'total_orders'   => $orderCollection->count(),
        'delivered'      => $orderCollection->where('order_status', 'delivered')->count(),
        'in_progress'    => $orderCollection->where('order_status', 'processing')->count(),
        'delayed'        => $orderCollection->where('order_status', 'failed')->count(),
        'cancelled'      => $orderCollection->where('order_status', 'canceled')->count(),
        'total_purchase' => $totalPurchase,
        'total_paid'     => $totalPaid,
        'outstanding'    => $totalPurchase - $totalPaid,
    ];

    // ---------------- STORE WISE TOTALS ----------------
    $storeTotals = DB::table('orders as o')
        ->leftJoin('order_payments as op', function ($join) {
            $join->on('o.id', '=', 'op.order_id')
                 ->where('op.payment_status', 'complete');
        })
        ->when($status, fn ($q) => $q->where('o.order_status', $status))
        ->when($productId || $supplierId, function ($q) use ($productId, $supplierId) {
            $q->whereExists(function ($sub) use ($productId, $supplierId) {
                $sub->select(DB::raw(1))
                    ->from('order_details as d')
                    ->join('products as pr', 'pr.id', '=', 'd.product_id')
                    ->whereColumn('d.order_id', 'o.id');

                if ($productId) $sub->where('d.product_id', $productId);
                if ($supplierId) $sub->where('pr.supplier_id', $supplierId);
            });
        })
        ->whereNotNull('o.store_id')
        ->groupBy('o.store_id')
        ->select(
            'o.store_id',
            DB::raw('SUM(o.order_amount + COALESCE(o.total_tax_amount,0)) AS store_total_order'),
            DB::raw('SUM(COALESCE(op.amount,0)) AS store_total_paid'),
            DB::raw('SUM((o.order_amount + COALESCE(o.total_tax_amount,0)) - COALESCE(op.amount,0)) AS store_arrear')
        )
        ->get()
        ->keyBy('store_id');

    // ---------------- ORDER DETAIL QUANTITIES ----------------
    foreach ($orders as $order) {
        foreach ($order->details as $detail) {
            $lastEdit = $detail->editLogs->sortByDesc('id')->first();

            if ($lastEdit) {
                $detail->current_qty = $lastEdit->new_quantity;
                $detail->return_qty  = max(0, $lastEdit->old_quantity - $lastEdit->new_quantity);
            } else {
                $detail->current_qty = $detail->quantity;
                $detail->return_qty  = 0;
            }
        }

        // Order-level info
        $order->first_invoice_number = $order->details->first()?->invoice_number;
        $order->first_expected_date  = $order->details->first()?->expected_date;
        $order->first_order_user     = $order->details->first()?->order_user;
    }

    return view('admin-views.order.ordermanagement', [
        'orders'      => $orders,
        'suppliers'   => Supplier::all(),
        'products'    => Product::all(),
        'summary'     => $summary,
        'storeTotals' => $storeTotals,
    ]);
}
public function updateOrder(Request $request, $id)
{
    $request->validate([
        'order_status'    => 'required',
        'paid_amount'     => 'nullable|numeric|min:0',
        'invoice_number'  => 'nullable|string',
        'expected_date'   => 'nullable|date',
        'payment_method'  => 'nullable|string',
        'payment_status'  => 'nullable|string|in:pending,complete,failed',
    ]);

    $order = Order::with('payments', 'orderDetails', 'store')->findOrFail($id);
    $orderTotal = (float) $order->order_amount;
    $incomingPaid = $request->paid_amount ?? 0;
    $storeId = $order->store_id;

    // -------------------- UPDATE ORDER STATUS --------------------
    $order->order_status = $request->order_status;
    $order->save();

    // -------------------- HANDLE REJECTED OR RETURNED --------------------
    if (in_array(strtolower($request->order_status), ['rejected', 'returned'])) {
        // Reset order amounts
        $order->order_amount = 0;
        $order->total_tax_amount = 0;
        $order->save();

        // Reset all order detail prices
        foreach ($order->orderDetails as $detail) {
            $detail->price = 0;
            $detail->tax_amount = 0;
            $detail->save();
        }

        // Mark existing payments as failed
        foreach ($order->payments as $payment) {
            $payment->payment_status = 'failed';
            $payment->save();
        }

        // Skip normal payment logic
        return back()->with('success', "Order {$order->id} has been {$request->order_status}.");
    }

    // -------------------- EXISTING PAYMENT LOGIC --------------------
    if ($incomingPaid > 0) {
        if ($storeId) {
            $storeOrders = Order::where('store_id', $storeId)->get();
            $storeTotalAmount = 0;
            $storeTotalPaid   = 0;

            foreach ($storeOrders as $sOrder) {
                $sTotal = (float) $sOrder->order_amount;
                $sPaid  = OrderPayment::where('order_id', $sOrder->id)
                            ->where('payment_status', 'complete')
                            ->sum('amount');
                $storeTotalAmount += $sTotal;
                $storeTotalPaid   += $sPaid;
            }

            $storeArrear = round($storeTotalAmount - $storeTotalPaid, 2);

            if ($incomingPaid > $storeArrear) {
                return back()->withErrors([
                    'paid_amount' => "Payment exceeds store arrear amount: ₹" . number_format($storeArrear,2)
                ]);
            }
        } else {
            $alreadyPaid = OrderPayment::where('order_id', $order->id)
                            ->where('payment_status', 'complete')
                            ->sum('amount');
            $orderArrear = $orderTotal - $alreadyPaid;

            if ($incomingPaid > $orderArrear) {
                return back()->withErrors([
                    'paid_amount' => "Payment exceeds remaining due for this order: ₹" . number_format($orderArrear,2)
                ]);
            }
        }
    }

    // -------------------- UPDATE ORDER DETAILS --------------------
    foreach ($order->orderDetails as $detail) {
        if ($request->filled('invoice_number')) {
            $detail->invoice_number = $request->invoice_number;
        }
        if ($request->filled('expected_date')) {
            $detail->expected_date = $request->expected_date;
        }
        $detail->save();
    }

    // -------------------- ADD PAYMENT --------------------
    if ($incomingPaid > 0) {
        // Apply to current order
        $alreadyPaid = OrderPayment::where('order_id', $order->id)
            ->where('payment_status', 'complete')
            ->sum('amount');
        $currentOrderArrear = $orderTotal - $alreadyPaid;
        $applyToCurrent = min($incomingPaid, $currentOrderArrear);

        if ($applyToCurrent > 0) {
            $order->payments()->create([
                'amount'         => $applyToCurrent,
                'payment_method' => $request->payment_method ?? 'manual',
                'payment_status' => $request->payment_status ?? 'complete',
                'payment_date'   => now(),
            ]);
            $incomingPaid -= $applyToCurrent;
        }

        // Apply remaining to other store orders
        if ($incomingPaid > 0 && $storeId) {
            $otherOrders = Order::where('store_id', $storeId)
                ->where('id', '!=', $order->id)
                ->orderBy('id')
                ->get();

            foreach ($otherOrders as $o) {
                if ($incomingPaid <= 0) break;

                $oTotal = (float) $o->order_amount;
                $oPaid  = OrderPayment::where('order_id', $o->id)
                            ->where('payment_status', 'complete')
                            ->sum('amount');

                $oArrear = $oTotal - $oPaid;
                if ($oArrear <= 0) continue;

                $pay = min($incomingPaid, $oArrear);

                $o->payments()->create([
                    'amount'         => $pay,
                    'payment_method' => $request->payment_method ?? 'manual',
                    'payment_status' => $request->payment_status ?? 'complete',
                    'payment_date'   => now(),
                ]);

                $incomingPaid -= $pay;
            }
        }
    }

    // -------------------- UPDATE PAYMENT STATUS PER ORDER --------------------
    $allStoreOrders = $storeId ? Order::where('store_id', $storeId)->get() : collect([$order]);
    foreach ($allStoreOrders as $o) {
        $totalPaid = OrderPayment::where('order_id', $o->id)
            ->where('payment_status', 'complete')
            ->sum('amount');
        $o->payment_status = $totalPaid >= $o->order_amount ? 'Paid' : 'Unpaid';
        $o->save();
    }

    return back()->with('success', 'Order updated successfully.');
}



public function createOrder()
{
    return view('admin-views.order.create', [
        'suppliers' => Supplier::all(),
        'products' => Product::select(
    'id',
    'name',
    'price',
    'tax',
    'tax_type'
)->get()


    ]);
}

// AJAX: Get products by supplier
public function getSupplierProducts($supplierId)
{
    return Product::where('supplier_id', $supplierId)->get();
}

// AJAX: Get product price
public function getProductPrice($id)
{
    return Product::find($id);
}
public function storeOrder(Request $request)
{
    $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'order_date' => 'required|date',
        'expected_date' => 'nullable|date',
        'delivery_date' => 'nullable|date',
        'invoice_no' => 'nullable',
        'order_status' => 'required',
        'payment_mode' => 'required',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.qty' => 'required|numeric|min:1',
        'products.*.price' => 'required|numeric|min:0',
        'order_user' => 'nullable|string|max:255',
    ]);

    // ---------------- CREATE ORDER ----------------
    $order = Order::create([
        'order_amount' => 0,
        'total_tax_amount' => 0,
        'payment_status' => 'unpaid',
        'order_status' => $request->order_status,
        'payment_method' => $request->payment_mode,
        'date' => $request->order_date,
        'expected_date' => $request->expected_date,
        'delivery_date' => $request->delivery_date,
        'invoice_number' => $request->invoice_no,
        'order_note' => $request->comment,
    ]);

    $subTotal = 0;
    $totalTax = 0;

    // ---------------- ORDER DETAILS ----------------
    foreach ($request->products as $item) {

        $product = Product::findOrFail($item['product_id']);

        // Get the actual price (use item price if provided, otherwise product price)
        $actualPrice = ($item['price'] ?? 0) > 0 ? $item['price'] : $product->price;

        // Sub total
        $lineTotal = $actualPrice * $item['qty'];

        // ---------------- TAX CALCULATION ----------------
        $taxAmount = 0;

        if ($product->tax > 0) {
            if ($product->tax_type === 'percent') {
                $taxAmount = ($lineTotal * $product->tax) / 100;
            } else {
                $taxAmount = $product->tax * $item['qty'];
            }
        }

        $subTotal += $lineTotal;
        $totalTax += $taxAmount;

        // Snapshot of product
        $productDetails = [
            "id" => $product->id,
            "name" => $product->name,
            "price" => $actualPrice, // Store the actual price used
            "tax" => $product->tax,
            "tax_type" => $product->tax_type,
            "unit" => $product->unit,
        ];

        // Save order details
        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'order_user' => $request->order_user,
            'price' => $actualPrice, // Use the calculated actual price
            'quantity' => $item['qty'],

            // ✅ STORE PRODUCT TAX HERE
            'tax_amount' => round($taxAmount, 2),

            'discount_on_product' => 0,
            'discount_type' => 'amount',
            'unit' => $product->unit,
            'delivery_date' => $request->delivery_date,
            'expected_date' => $request->expected_date,
            'invoice_number' => $request->invoice_no,
            'vat_status' => 'excluded',
            'variation' => json_encode([]),
            'product_details' => json_encode($productDetails),
        ]);
    }

    // ---------------- UPDATE ORDER TOTAL ----------------
    $grandTotal = $subTotal + $totalTax;

    $order->update([
        'order_amount' => $grandTotal,
        'total_tax_amount' => $totalTax
    ]);

    // ---------------- REJECT / RETURN ----------------
    if (in_array(strtolower($request->order_status), ['rejected', 'returned'])) {

        $order->update([
            'order_amount' => 0,
            'total_tax_amount' => 0
        ]);

        OrderDetail::where('order_id', $order->id)
            ->update(['price' => 0, 'tax_amount' => 0]);

        OrderPayment::where('order_id', $order->id)
            ->update(['payment_status' => 'failed']);

        return redirect()->route('admin.orders.ordermanagement')
            ->with('success', "Order {$order->id} has been {$request->order_status}");
    }

    // ---------------- PAYMENT ----------------
    $paid = $request->paid ?? 0;

    $totalPaidSoFar = OrderPayment::where('order_id', $order->id)->sum('amount');

    // ✅ IMPORTANT: validate against GRAND TOTAL
    if (($totalPaidSoFar + $paid) > $grandTotal) {
        return back()->withErrors([
            'paid' => "Paid amount cannot exceed ₹" . number_format($grandTotal, 2)
        ])->withInput();
    }

    $paymentStatus = ($paid > 0) ? 'complete' : 'incomplete';

    OrderPayment::create([
        'order_id' => $order->id,
        'payment_method' => $request->payment_mode,
        'amount' => $paid,
        'first_payment' => $paid,
        'payment_status' => $paymentStatus,
        'payment_date' => now(),
    ]);

    $totalPaid = OrderPayment::where('order_id', $order->id)->sum('amount');

    $order->payment_status = ($totalPaid >= $grandTotal) ? 'complete' : 'partial';
    $order->save();

    return redirect()->route('admin.orders.ordermanagement')
        ->with('success', 'Order created successfully.');
}

    /**
     * @param $id
     * @return View|Factory|RedirectResponse|Application
     */
    public function details($id): Factory|View|Application|RedirectResponse
    {
        $order = $this->order
            ->with([
                'details',
                'details.product', // ✅ Load actual product for correct price
                'details.editLogs', // ✅ Load edit logs for each detail
                'customer',
                'branch',
                'delivery_man',
                'store',
                'payments',
                'offline_payment',
                'editLogs', // ✅ fetch edit logs
                'editLogs.deliveryMan', // optional to show DM name
                'editLogs.orderDetail',
                'editLogs.orderDetail.product'
            ])
            ->where(['id' => $id])
            ->first();

        $deliverymanList = $this->delivery_man->where(['is_active' => 1])
            ->where(function ($query) use ($order) {
                $query->where('branch_id', $order->branch_id)
                    ->orWhere('branch_id', 0);
            })->get();

        if ($order) {
            return view('admin-views.order.order-view', compact('order', 'deliverymanList'));
        } else {
            Toastr::info(translate('No more orders!'));
            return back();
        }
    }


    /**
     * @param $order
     * @param $amount
     * @return void
     */
    private function calculateRefundAmount($order, $amount): void
    {
        $customer = $this->user->find($order['user_id']);
        $wallet = CustomerLogic::create_wallet_transaction($customer->id, $amount, 'refund', $order['id']);
        if ($wallet) {
            $customer->wallet_balance += $amount;
        }
        $customer->save();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): \Illuminate\Http\RedirectResponse
    {
        $order = $this->order->find($request->id);

        if (in_array($order->order_status, ['returned', 'delivered', 'failed', 'canceled'])) {
            Toastr::warning(translate('you_can_not_change_the_status_of ' . $order->order_status . ' order'));
            return back();
        }

        if ($request->order_status == 'delivered' && $order['payment_status'] != 'paid') {
            Toastr::warning(translate('you_can_not_delivered_a_order_when_order_status_is_not_paid. please_update_payment_status_first'));
            return back();
        }

        if ($request->order_status == 'delivered' && $order['transaction_reference'] == null && !in_array($order['payment_method'], ['cash_on_delivery', 'wallet_payment', 'offline_payment'])) {
            Toastr::warning(translate('add_your_payment_reference_first'));
            return back();
        }

        if (($request->order_status == 'out_for_delivery' || $request->order_status == 'delivered') && $order['delivery_man_id'] == null && $order['order_type'] != 'self_pickup') {
            Toastr::warning(translate('Please assign delivery man first!'));
            return back();
        }

        //refund amount to wallet
        if (in_array($request['order_status'], ['returned', 'failed', 'canceled']) && $order['is_guest'] == 0 && isset($order->customer) && Helpers::get_business_settings('wallet_status') == 1) {

            if ($order['payment_method'] == 'wallet_payment' && $order->partial_payment->isEmpty()) {
                $this->calculateRefundAmount(order: $order, amount: $order->order_amount);
            }

            if ($order['payment_method'] != 'cash_on_delivery' && $order['payment_method'] != 'wallet_payment' && $order['payment_method'] != 'offline_payment' && $order->partial_payment->isEmpty()) {
                $this->calculateRefundAmount(order: $order, amount: $order->order_amount);
            }

            if ($order['payment_method'] == 'offline_payment' && $order['payment_status'] == 'paid' && $order->partial_payment->isEmpty()) {
                $this->calculateRefundAmount(order: $order, amount: $order['order_amount']);
            }

            if ($order->partial_payment->isNotEmpty()) {
                $partial_payment_total = $order->partial_payment->sum('paid_amount');
                $this->calculateRefundAmount(order: $order, amount: $partial_payment_total);
            }
        }

        //stock adjust
        if ($request->order_status == 'returned' || $request->order_status == 'failed' || $request->order_status == 'canceled') {
            foreach ($order->details as $detail) {
                if (!isset($detail->variant)) {
                    if ($detail['is_stock_decreased'] == 1) {
                        $product = $this->product->find($detail['product_id']);
                        if (!isset($detail->variant)) {
                            dd('ache');
                        }

                        if ($product != null) {
                            $type = json_decode($detail['variation'])[0]->type;
                            $variationStore = [];
                            foreach (json_decode($product['variations'], true) as $var) {
                                if ($type == $var['type']) {
                                    $var['stock'] += $detail['quantity'];
                                }
                                $variationStore[] = $var;
                            }
                            $this->product->where(['id' => $product['id']])->update([
                                'variations' => json_encode($variationStore),
                                'total_stock' => $product['total_stock'] + $detail['quantity'],
                            ]);
                            $this->order_detail->where(['id' => $detail['id']])->update([
                                'is_stock_decreased' => 0,
                            ]);
                        } else {
                            Toastr::warning(translate('Product_deleted'));
                        }
                    }
                }
            }
        } else {
            foreach ($order->details as $detail) {
                if (!isset($detail->variant)) {
                    if ($detail['is_stock_decreased'] == 0) {

                        $product = $this->product->find($detail['product_id']);
                        if ($product != null) {
                            foreach ($order->details as $c) {
                                $product = $this->product->find($c['product_id']);
                                $type = json_decode($c['variation'])[0]->type;
                                foreach (json_decode($product['variations'], true) as $var) {
                                    if ($type == $var['type'] && $var['stock'] < $c['quantity']) {
                                        Toastr::error(translate('Stock is insufficient!'));
                                        return back();
                                    }
                                }
                            }

                            $type = json_decode($detail['variation'])[0]->type;
                            $variationStore = [];
                            foreach (json_decode($product['variations'], true) as $var) {
                                if ($type == $var['type']) {
                                    $var['stock'] -= $detail['quantity'];
                                }
                                $variationStore[] = $var;
                            }
                            $this->product->where(['id' => $product['id']])->update([
                                'variations' => json_encode($variationStore),
                                'total_stock' => $product['total_stock'] - $detail['quantity'],
                            ]);
                            $this->order_detail->where(['id' => $detail['id']])->update([
                                'is_stock_decreased' => 1,
                            ]);
                        } else {
                            Toastr::warning(translate('Product_deleted'));
                        }

                    }
                }
            }
        }

        if ($request->order_status == 'delivered') {
            if ($order->is_guest == 0) {
                if ($order->user_id) {
                    CustomerLogic::create_loyalty_point_transaction($order->user_id, $order->id, $order->order_amount, 'order_place');
                }

                $user = $this->user->find($order->user_id);
                $isFirstOrder = $this->order->where(['user_id' => $user->id, 'order_status' => 'delivered'])->count('id');
                $referredByUser = $this->user->find($user->referred_by);

                if ($isFirstOrder < 2 && isset($user->referred_by) && isset($referredByUser)) {
                    if ($this->business_setting->where('key', 'ref_earning_status')->first()->value == 1) {
                        CustomerLogic::referral_earning_wallet_transaction($order->user_id, 'referral_order_place', $referredByUser->id);
                    }
                }
            }

            if ($order['payment_method'] == 'cash_on_delivery') {
                $partialData = OrderPartialPayment::where(['order_id' => $order->id])->first();
                if ($partialData) {
                    $partial = new OrderPartialPayment;
                    $partial->order_id = $order['id'];
                    $partial->paid_with = 'cash_on_delivery';
                    $partial->paid_amount = $partialData->due_amount;
                    $partial->due_amount = 0;
                    $partial->save();
                }
            }
        }

        $order->order_status = $request->order_status;
        $order->save();

        // Auto-create full return logs for 'returned' status (Option A visibility)
        if ($request->order_status == 'returned') {
            $this->ensureFullReturnLogs($order);
        }


        $message = Helpers::order_status_update_message($request->order_status);
        $languageCode = $order->is_guest == 0 ? ($order->customer ? $order->customer->language_code : 'en') : ($order->guest ? $order->guest->language_code : 'en');
        $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);

        if ($languageCode != 'en') {
            $message = $this->translate_message($languageCode, $request->order_status);
        }
        $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type' => 'order'
                ];
                Helpers::send_push_notif_to_device($customerFcmToken, $data);
            }
        } catch (\Exception $e) {
            Toastr::warning(\App\CentralLogics\translate('Push notification failed for Customer!'));
        }

        if ($request->order_status == 'processing' && $order->delivery_man != null) {
            $deliverymanFcmToken = $order->delivery_man->fcm_token;
            $message = Helpers::order_status_update_message('deliveryman_order_processing');
            $deliverymanLanguageCode = $order->delivery_man->language_code ?? 'en';

            if ($deliverymanLanguageCode != 'en') {
                $message = $this->translate_message($deliverymanLanguageCode, 'deliveryman_order_processing');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            try {
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order'
                    ];
                    Helpers::send_push_notif_to_device($deliverymanFcmToken, $data);
                }
            } catch (\Exception $e) {
                Toastr::warning(\App\CentralLogics\translate('Push notification failed for DeliveryMan!'));
            }
        }

        Toastr::success(translate('Order status updated!'));
        return back();
    }

    /**
     * @param $order_id
     * @param $delivery_man_id
     * @return JsonResponse
     */
    public function addDeliveryman($order_id, $delivery_man_id): \Illuminate\Http\JsonResponse
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }

        $order = $this->order->find($order_id);

        // Only allow change if order is not delivered, returned, failed, or canceled
        if (in_array($order->order_status, ['pending', 'confirmed', 'delivered', 'returned', 'failed', 'canceled'])) {
            return response()->json(['status' => false, 'message' => 'Cannot change delivery man for this order'], 200);
        }

        $oldDeliveryManId = $order->delivery_man_id;
        $order->delivery_man_id = $delivery_man_id;
        $order->save();

        // Update delivery trip
        $trip = DeliveryTrip::where('trip_number', $order->trip_number)->first();
        if ($trip) {
            $orderIds = collect($trip->order_ids);

            // Remove order from old delivery man's trip if different
            if ($oldDeliveryManId && $oldDeliveryManId != $delivery_man_id) {
                $trip->order_ids = $orderIds->filter(fn($id) => $id != $order->id)->values();
                $trip->save();
            }

            // Check if new delivery man already has a trip for today
            $newTrip = DeliveryTrip::where('delivery_man_id', $delivery_man_id)
                ->whereDate('created_at', now()->toDateString())
                ->first();

            if ($newTrip) {
                $newTrip->order_ids = array_merge($newTrip->order_ids ?? [], [$order->id]);
                $order->trip_number = $newTrip->trip_number;
                $order->save();
                $newTrip->save();
            } else {
                // Create new trip for new delivery man
                $newTripNumber = 'TRIP-' . strtoupper(Str::random(6));
                DeliveryTrip::create([
                    'trip_number' => $newTripNumber,
                    'delivery_man_id' => $delivery_man_id,
                    'order_ids' => [$order->id],
                ]);
                $order->trip_number = $newTripNumber;
                $order->save();
            }
        }

        Toastr::success('Deliveryman successfully assigned/changed!');
        return response()->json(['status' => true], 200);
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function paymentStatus(Request $request): \Illuminate\Http\RedirectResponse
    {
        $order = $this->order->find($request->id);

        if ($order->payment_method == 'offline_payment' && isset($order->offline_payment) && $order->offline_payment?->status != 1) {
            Toastr::warning(translate('please_verify_your_offline_payment_verification'));
            return back();
        }

        if ($request->payment_status == 'paid' && $order['transaction_reference'] == null && $order['payment_method'] != 'cash_on_delivery') {
            Toastr::warning(translate('Add your payment reference code first!'));
            return back();
        }

        if ($request->payment_status == 'paid' && $order['order_status'] == 'pending') {
            $order->order_status = 'confirmed';

            $message = Helpers::order_status_update_message('confirmed');
            $languageCode = $order->is_guest == 0 ? ($order->customer ? $order->customer->language_code : 'en') : ($order->guest ? $order->guest->language_code : 'en');
            $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);

            if ($languageCode != 'en') {
                $message = $this->translate_message($languageCode, 'confirmed');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            try {
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order'
                    ];
                    Helpers::send_push_notif_to_device($customerFcmToken, $data);
                }
            } catch (\Exception $e) {
                //
            }

        }
        $order->payment_status = $request->payment_status;
        $order->save();
        Toastr::success(translate('Payment status updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function updateShipping(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required',
        ]);

        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'road' => $request->road,
            'house' => $request->house,
            'floor' => $request->floor,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('customer_addresses')->where('id', $id)->update($address);
        Toastr::success(translate('Delivery Information updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function updateTimeSlot(Request $request)
    {
        if ($request->ajax()) {
            $order = $this->order->find($request->id);
            $order->time_slot_id = $request->timeSlot;
            $order->save();
            $data = $request->timeSlot;

            return response()->json($data);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function updateDeliveryDate(Request $request)
    {
        if ($request->ajax()) {
            $order = $this->order->find($request->id);
            $order->delivery_date = $request->deliveryDate;
            $order->save();
            $data = $request->deliveryDate;
            return response()->json($data);
        }
    }

    /**
     * @param $id
     * @return Factory|View|Application
     */
    public function generateInvoice($id): View|Factory|Application
    {
        $order = $this->order->with([
            'details', 
            'details.product',
            'details.editLogs',
            'customer',
            'branch',
            'delivery_man',
            'payments',
            'editLogs',
            'editLogs.orderDetail',
            'editLogs.deliveryMan'
        ])->where('id', $id)->first();
        $footer_text = $this->business_setting->where(['key' => 'footer_text'])->first();
        return view('admin-views.order.invoice', compact('order', 'footer_text'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function addPaymentReferenceCode(Request $request, $id): RedirectResponse
    {
        $this->order->where(['id' => $id])->update([
            'transaction_reference' => $request['transaction_reference'],
        ]);

        Toastr::success(translate('Payment reference code is added!'));
        return back();
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function branchFilter($id): RedirectResponse
    {
        session()->put('branch_filter', $id);
        return back();
    }

    /**
     * @param Request $request
     * @param $status
     * @return string|StreamedResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportOrders(Request $request, $status): StreamedResponse|string
    {
        $queryParam = [];
        $search = $request['search'];
        $branchId = $request['branch_id'];
        $startDate = $request['start_date'];
        $endDate = $request['end_date'];

        $query = $this->order->with(['customer', 'branch'])
            ->when((!is_null($branchId) && $branchId != 'all'), function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })->when((!is_null($startDate) && !is_null($endDate)), function ($query) use ($startDate, $endDate) {
                return $query->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            });

        if ($status != 'all') {
            $query->where(['order_status' => $status]);
        }

        $queryParam = ['branch_id' => $branchId, 'start_date' => $startDate, 'end_date' => $endDate];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('payment_status', 'like', "{$value}%");
                }
            });
            $queryParam['search'] = $search;
        }

        $orders = $query->notPos()->orderBy('id', 'desc')->get();

        $storage = [];
        foreach ($orders as $order) {
            $branch = $order->branch ? $order->branch->name : '';
            $customer = $order->customer ? $order->customer->f_name . ' ' . $order->customer->l_name : 'Customer Deleted';
            $deliveryman = $order->delivery_man ? $order->delivery_man->f_name . ' ' . $order->delivery_man->l_name : '';
            $timeslot = $order->time_slot ? $order->time_slot->start_time . ' - ' . $order->time_slot->end_time : '';

            $storage[] = [
                'order_id' => $order['id'],
                'customer' => $customer,
                'order_amount' => $order['order_amount'],
                'coupon_discount_amount' => $order['coupon_discount_amount'],
                'payment_status' => $order['payment_status'],
                'order_status' => $order['order_status'],
                'total_tax_amount' => $order['total_tax_amount'],
                'payment_method' => $order['payment_method'],
                'transaction_reference' => $order['transaction_reference'],
                'delivery_man' => $deliveryman,
                'delivery_charge' => $order['delivery_charge'],
                'coupon_code' => $order['coupon_code'],
                'order_type' => $order['order_type'],
                'branch' => $branch,
                'time_slot_id' => $timeslot,
                'date' => $order['date'],
                'delivery_date' => $order['delivery_date'],
                'extra_discount' => $order['extra_discount'],
            ];
        }
        return (new FastExcel($storage))->download('orders.xlsx');
    }

    /**
     * @param $order_id
     * @param $status
     * @return JsonResponse
     */
    public function verifyOfflinePayment($order_id, $status): JsonResponse
    {
        $offlineData = OfflinePayment::where(['order_id' => $order_id])->first();

        if (!isset($offlineData)) {
            return response()->json(['status' => false, 'message' => translate('offline data not found')], 200);
        }

        $order = Order::find($order_id);
        if (!isset($order)) {
            return response()->json(['status' => false, 'message' => translate('order not found')], 200);
        }

        if ($status == 1) {
            if ($order->order_status == 'canceled') {
                return response()->json(['status' => false, 'message' => translate('Canceled order can not be verified')], 200);
            }

            $offlineData->status = $status;
            $offlineData->save();

            $order->order_status = 'confirmed';
            $order->payment_status = 'paid';
            $order->save();

            $message = Helpers::order_status_update_message('confirmed');
            $languageCode = $order->is_guest == 0 ? ($order->customer ? $order->customer->language_code : 'en') : ($order->guest ? $order->guest->language_code : 'en');
            $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);

            if ($languageCode != 'en') {
                $message = $this->translate_message($languageCode, 'confirmed');
            }
            $value = $this->dynamic_key_replaced_message(message: $message, type: 'order', order: $order);

            try {
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order'
                    ];
                    Helpers::send_push_notif_to_device($customerFcmToken, $data);
                }
            } catch (\Exception $e) {
                //
            }

        } elseif ($status == 2) {
            $offlineData->status = $status;
            $offlineData->save();

            $customerFcmToken = null;
            if ($order->is_guest == 0) {
                $customerFcmToken = $order->customer ? $order->customer->cm_firebase_token : null;
            } elseif ($order->is_guest == 1) {
                $customerFcmToken = $order->guest ? $order->guest->fcm_token : null;
            }
            if ($customerFcmToken != null) {
                try {
                    $data = [
                        'title' => translate('Order'),
                        'description' => translate('Offline payment is not verified'),
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'order',
                    ];
                    Helpers::send_push_notif_to_device($customerFcmToken, $data);
                } catch (\Exception $e) {
                }
            }
        }
        return response()->json(['status' => true, 'message' => translate("offline payment verify status changed")], 200);
    }

    public function updateOrderDeliveryArea(Request $request, $order_id)
    {
        $request->validate([
            'selected_area_id' => 'required'
        ]);

        $order = $this->order->find($order_id);
        if (!$order) {
            Toastr::warning(translate('order not found'));
            return back();
        }

        if ($order->order_status == 'delivered') {
            Toastr::warning(translate('you_can_not_change_the_area_once_the_order_status_is_delivered'));
            return back();
        }

        $branch = Branch::with(['delivery_charge_setup', 'delivery_charge_by_area'])
            ->where(['id' => $order['branch_id']])
            ->first(['id', 'name', 'status']);

        if ($branch->delivery_charge_setup->delivery_charge_type != 'area') {
            Toastr::warning(translate('this branch selected delivery type is not area'));
            return back();
        }

        $area = DeliveryChargeByArea::where(['id' => $request['selected_area_id'], 'branch_id' => $order->branch_id])->first();
        if (!$area) {
            Toastr::warning(translate('Area not found'));
            return back();
        }

        $order->delivery_charge = $area->delivery_charge;
        $order->save();

        $orderArea = $this->orderArea->firstOrNew(['order_id' => $order_id]);
        $orderArea->area_id = $request->selected_area_id;
        $orderArea->save();

        $customerFcmToken = $order->is_guest == 0 ? ($order->customer ? $order->customer->cm_firebase_token : null) : ($order->guest ? $order->guest->fcm_token : null);
        try {
            if ($customerFcmToken != null) {
                $data = [
                    'title' => translate('Order'),
                    'description' => translate('order delivery area updated'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order'
                ];
                Helpers::send_push_notif_to_device($customerFcmToken, $data);
            }
        } catch (\Exception $e) {
            //
        }

        Toastr::success(translate('Order delivery area updated successfully.'));
        return back();
    }


    /**
     * If an order is marked returned but has no order_edit_logs,
     * create FULL RETURN logs (one per order_detail) so Admin can manage data.
     */
    protected function ensureFullReturnLogs($order): void
    {
        if (!$order) { return; }

        $exists = OrderEditLog::where('order_id', $order->id)->exists();
        if ($exists) { return; }

        $details = OrderDetail::where('order_id', $order->id)->get();
        if ($details->isEmpty()) { return; }

        $dmId = $order->delivery_man_id ?? 0;

        $rows = [];
        $now = now();

        foreach ($details as $d) {
            $oldQty = (int) $d->quantity;
            $rows[] = [
                'order_id' => $order->id,
                'order_detail_id' => $d->id,
                'delivery_man_id' => $dmId ?: 0,
                'reason' => 'Full Return',
                'photo' => null,
                'old_quantity' => $oldQty,
                'new_quantity' => 0,
                'old_price' => (float) ($d->price * $oldQty),
                'new_price' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('order_edit_logs')->insert($rows);
    }

    /**
     * Adjust order item quantity/price
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function adjustItem(Request $request): RedirectResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_detail_id' => 'required|exists:order_details,id',
            'adjust_type' => 'required|in:quantity,return',
            'reason' => 'required|string|max:255',
            'new_quantity' => 'required_if:adjust_type,quantity|integer|min:0',
            'return_quantity' => 'required_if:adjust_type,return|integer|min:1',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $order = $this->order->findOrFail($request->order_id);
            $orderDetail = $this->order_detail->findOrFail($request->order_detail_id);

            $oldQty = (int) $orderDetail->quantity;
            $unitPrice = (float) $orderDetail->price;

            // Calculate new quantity based on adjustment type
            if ($request->adjust_type === 'return') {
                $returnQty = min((int) $request->return_quantity, $oldQty);
                $newQty = max(0, $oldQty - $returnQty);
            } else {
                $newQty = (int) $request->new_quantity;
            }

            $oldPrice = $unitPrice * $oldQty;
            $newPrice = $unitPrice * $newQty;
            $priceDiff = $newPrice - $oldPrice;

            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('order_edit_photos', 'public');
            }

            // Determine action type
            $action = OrderEditLog::determineAction($oldQty, $newQty, $request->reason);
            $returnType = OrderEditLog::determineReturnType($oldQty, $newQty);

            // Get current admin info
            $admin = auth('admin')->user();

            // Create edit log
            OrderEditLog::create([
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'delivery_man_id' => $order->delivery_man_id ?? 0,
                'action' => $action,
                'edited_by_type' => OrderEditLog::EDITED_BY_ADMIN,
                'edited_by_id' => $admin ? $admin->id : null,
                'reason' => $request->reason,
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'unit_price' => $unitPrice,
                'discount_per_unit' => $orderDetail->discount ?? 0,
                'tax_per_unit' => $orderDetail->tax_amount ?? 0,
                'price_difference' => $priceDiff,
                'quantity_difference' => $newQty - $oldQty,
                'return_type' => $returnType,
                'photo' => $photoPath,
                'notes' => $request->notes,
                'order_amount_before' => $order->order_amount,
                'order_amount_after' => $order->order_amount + $priceDiff,
            ]);

            // Update order detail quantity
            $orderDetail->quantity = $newQty;
            $orderDetail->save();

            // Update order amount
            $order->order_amount = $order->order_amount + $priceDiff;
            
            // If all items are returned, mark order as returned
            $allItemsReturned = $order->details()->sum('quantity') == 0;
            if ($allItemsReturned) {
                $order->order_status = 'returned';
            }
            
            $order->save();

            DB::commit();

            Toastr::success(translate('Order item adjusted successfully.'));

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to adjust order item: ') . $e->getMessage());
        }

        return back();
    }

}
