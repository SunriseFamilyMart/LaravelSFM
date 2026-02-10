<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use function App\CentralLogics\translate;

class PickingController extends Controller
{
    public function __construct(
        private Branch $branch,
        private Order $order,
        private OrderDetail $orderDetail,
        private Product $product,
        private Store $store
    ) {
    }

    /**
     * Display the picking list with filters
     *
     * @param Request $request
     * @return Factory|View|Application
     */
    public function index(Request $request): View|Factory|Application
    {
        $branches = $this->branch->active()->get();
        $routes = $this->store->select('route_name')
            ->distinct()
            ->whereNotNull('route_name')
            ->where('route_name', '!=', '')
            ->orderBy('route_name')
            ->pluck('route_name');

        // Get filter parameters
        $branchId = $request['branch_id'] ?? 'all';
        $route = $request['route'] ?? 'all';
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $startTime = $request['start_time'] ?? null;
        $endTime = $request['end_time'] ?? null;

        // Build query for pending and processing orders
        $query = $this->order->with(['details.product', 'branch', 'store', 'time_slot'])
            ->whereIn('order_status', ['pending', 'processing']);

        // Apply filters
        if ($branchId && $branchId != 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($route && $route != 'all') {
            $query->whereHas('store', function ($q) use ($route) {
                $q->where('route_name', $route);
            });
        }

        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        }

        if ($startTime && $endTime) {
            $query->whereTime('created_at', '>=', $startTime)
                ->whereTime('created_at', '<=', $endTime);
        }

        $orders = $query->latest()->paginate(15);

        return view('admin-views.picking.index', compact(
            'orders',
            'branches',
            'routes',
            'branchId',
            'route',
            'startDate',
            'endDate',
            'startTime',
            'endTime'
        ));
    }

    /**
     * Generate and download pick list PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        // Get filter parameters
        $branchId = $request['branch_id'] ?? 'all';
        $route = $request['route'] ?? 'all';
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $startTime = $request['start_time'] ?? null;
        $endTime = $request['end_time'] ?? null;

        // Build query for pending and processing orders
        $query = $this->order->with(['details.product', 'branch', 'store', 'time_slot'])
            ->whereIn('order_status', ['pending', 'processing']);

        // Apply same filters
        if ($branchId && $branchId != 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($route && $route != 'all') {
            $query->whereHas('store', function ($q) use ($route) {
                $q->where('route_name', $route);
            });
        }

        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        }

        if ($startTime && $endTime) {
            $query->whereTime('created_at', '>=', $startTime)
                ->whereTime('created_at', '<=', $endTime);
        }

        $orders = $query->latest()->get();

        // Prepare data for PDF
        $filterInfo = [
            'branch' => $branchId != 'all' ? $this->branch->find($branchId)?->name : translate('all_branches'),
            'route' => $route != 'all' ? $route : translate('all_routes'),
            'date_from' => $startDate ?? translate('not_specified'),
            'date_to' => $endDate ?? translate('not_specified'),
            'time_from' => $startTime ?? translate('not_specified'),
            'time_to' => $endTime ?? translate('not_specified'),
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('admin-views.picking.pick-list-pdf', compact('orders', 'filterInfo'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('pick-list-' . now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display a single picking order details
     *
     * @param int $id
     * @return Factory|View|Application
     */
    public function show($id): View|Factory|Application
    {
        $order = $this->order->with(['details.product', 'customer', 'branch', 'store', 'delivery_man', 'time_slot'])
            ->findOrFail($id);

        return view('admin-views.picking.show', compact('order'));
    }
}
