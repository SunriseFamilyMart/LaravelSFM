<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\BusinessSetting;
use App\Model\DeliveryMan;
use App\Model\PrOrder;
use App\Model\OrderDetail;
use App\Model\Product;


use App\Models\Supplier;
use App\Models\DeliveryChargeByArea;
use App\Models\OfflinePayment;
use App\Models\OrderArea;
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


class PrOrderController extends Controller
{
    
    public function prOrderManagement(Request $request)
{
    $supplierId = $request->supplier;
    $productId  = $request->product;
    $status     = $request->status;
    $limit      = $request->get('limit', 10);

    $orders = PrOrder::with([
            'store',
            'details.product.supplier',
            'payments' => fn ($q) => $q->where('payment_status', 'complete')
        ])
        ->when($status, fn ($q) => $q->where('order_status', $status))
        ->whereHas('details', function ($q) use ($productId, $supplierId) {
            if ($productId) {
                $q->where('product_id', $productId);
            }
            if ($supplierId) {
                $q->whereHas('product', fn ($p) => $p->where('supplier_id', $supplierId));
            }
        })
        ->latest()
        ->paginate($limit);

    /* ===== PAGE SUMMARY ===== */
    $collection = collect($orders->items());

    $totalPaid = $collection->sum(fn ($o) => $o->payments->sum('amount'));
    $totalPurchase = $collection->sum(fn ($o) => $o->order_amount + ($o->total_tax_amount ?? 0));

    /* ===== STORE TOTALS ===== */
    $storeTotals = DB::table('pr_orders as o')
        ->leftJoin('pr_order_payments as p', function ($join) {
            $join->on('o.id', '=', 'p.order_id')
                 ->where('p.payment_status', 'complete');
        })
        ->when($status, fn ($q) => $q->where('o.order_status', $status))
        ->select(
            'o.store_id',
            DB::raw('SUM(DISTINCT (o.order_amount + o.total_tax_amount)) as store_total'),
            DB::raw('SUM(COALESCE(p.amount,0)) as store_paid'),
            DB::raw('SUM(DISTINCT (o.order_amount + o.total_tax_amount)) - SUM(COALESCE(p.amount,0)) as store_due')
        )
        ->groupBy('o.store_id')
        ->get()
        ->keyBy('store_id');

    foreach ($orders as $order) {
        $order->first_invoice_number = $order->details->first()?->invoice_number;
        $order->first_expected_date  = $order->details->first()?->expected_date;
        $order->first_order_user     = $order->details->first()?->order_user;
    }

    return view('admin-views.pr-order.index', compact(
        'orders','totalPaid','totalPurchase','storeTotals'
    ));
}

}
