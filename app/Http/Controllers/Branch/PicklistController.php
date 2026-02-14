<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Models\OrderPickingItem;
use App\Models\Store;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PicklistController extends Controller
{
    public function __construct(
        private Order $order,
        private OrderDetail $orderDetail,
        private Product $product,
        private Store $store,
        private OrderPickingItem $pickingItem
    ) {}

    /**
     * Display the picklist generator with filters
     *
     * @param Request $request
     * @return Factory|View|Application
     */
    public function index(Request $request): View|Factory|Application
    {
        // Get filter parameters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('store_id');
        $routeName = $request->input('route_name');
        $pickingStatus = $request->input('picking_status');

        // Get all stores for filter dropdown
        $stores = $this->store->orderBy('store_name')->get();
        
        // Get unique routes for filter dropdown
        $routes = $this->store->whereNotNull('route_name')
            ->distinct()
            ->pluck('route_name')
            ->filter()
            ->sort()
            ->values();

        // Build query for picklist data
        $query = $this->orderDetail
            ->select(
                'order_details.product_id',
                'products.name',
                'products.weight',
                'stores.route_name',
                'stores.store_name',
                DB::raw('SUM(order_details.quantity) as total_quantity'),
                DB::raw('SUM(order_details.quantity * products.weight) as total_weight')
            )
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.branch_id', auth('branch')->id());

        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('orders.date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('orders.date', '<=', $endDate);
        }

        // Apply store filter
        if ($storeId) {
            $query->where('orders.store_id', $storeId);
        }

        // Apply route filter
        if ($routeName) {
            $query->where('stores.route_name', $routeName);
        }

        // Apply picking status filter
        if ($pickingStatus) {
            if ($pickingStatus === 'picked') {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('order_picking_items')
                        ->whereColumn('order_picking_items.order_id', 'orders.id')
                        ->whereColumn('order_picking_items.order_detail_id', 'order_details.id')
                        ->where('order_picking_items.status', 'picked');
                });
            } elseif ($pickingStatus === 'non_picked') {
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('order_picking_items')
                        ->whereColumn('order_picking_items.order_id', 'orders.id')
                        ->whereColumn('order_picking_items.order_detail_id', 'order_details.id')
                        ->where('order_picking_items.status', 'picked');
                });
            }
        }

        // Group by product and route
        $picklistData = $query
            ->groupBy('order_details.product_id', 'products.name', 'products.weight', 'stores.route_name', 'stores.store_name')
            ->orderBy('stores.route_name')
            ->orderBy('products.name')
            ->get();

        // Calculate total weight per route
        $routeTotals = $picklistData->groupBy('route_name')->map(function ($items) {
            return [
                'total_weight' => $items->sum('total_weight'),
                'total_quantity' => $items->sum('total_quantity'),
            ];
        });

        return view('branch-views.picklist.index', compact(
            'picklistData',
            'routeTotals',
            'stores',
            'routes',
            'startDate',
            'endDate',
            'storeId',
            'routeName',
            'pickingStatus'
        ));
    }

    /**
     * Export picklist to PDF
     *
     * @param Request $request
     * @return mixed
     */
    public function exportPdf(Request $request)
    {
        // Get filter parameters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('store_id');
        $routeName = $request->input('route_name');
        $pickingStatus = $request->input('picking_status');

        // Build query for picklist data
        $query = $this->orderDetail
            ->select(
                'order_details.product_id',
                'products.name',
                'products.weight',
                'stores.route_name',
                'stores.store_name',
                DB::raw('SUM(order_details.quantity) as total_quantity'),
                DB::raw('SUM(order_details.quantity * products.weight) as total_weight')
            )
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.branch_id', auth('branch')->id());

        // Apply filters (same as index)
        if ($startDate && $endDate) {
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('orders.date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('orders.date', '<=', $endDate);
        }

        if ($storeId) {
            $query->where('orders.store_id', $storeId);
        }

        if ($routeName) {
            $query->where('stores.route_name', $routeName);
        }

        if ($pickingStatus) {
            if ($pickingStatus === 'picked') {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('order_picking_items')
                        ->whereColumn('order_picking_items.order_id', 'orders.id')
                        ->whereColumn('order_picking_items.order_detail_id', 'order_details.id')
                        ->where('order_picking_items.status', 'picked');
                });
            } elseif ($pickingStatus === 'non_picked') {
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('order_picking_items')
                        ->whereColumn('order_picking_items.order_id', 'orders.id')
                        ->whereColumn('order_picking_items.order_detail_id', 'order_details.id')
                        ->where('order_picking_items.status', 'picked');
                });
            }
        }

        // Group by product and route
        $picklistData = $query
            ->groupBy('order_details.product_id', 'products.name', 'products.weight', 'stores.route_name', 'stores.store_name')
            ->orderBy('stores.route_name')
            ->orderBy('products.name')
            ->get();

        // Calculate total weight per route
        $routeTotals = $picklistData->groupBy('route_name')->map(function ($items) {
            return [
                'total_weight' => $items->sum('total_weight'),
                'total_quantity' => $items->sum('total_quantity'),
            ];
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('branch-views.picklist.pdf', [
            'picklistData' => $picklistData,
            'routeTotals' => $routeTotals,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'storeName' => $storeId ? $this->store->find($storeId)?->store_name : 'All Stores',
            'routeName' => $routeName ?? 'All Routes',
            'pickingStatus' => $pickingStatus ?? 'All',
        ]);

        return $pdf->download('picklist_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Export picklist to Excel
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        // Get filter parameters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('store_id');
        $routeName = $request->input('route_name');
        $pickingStatus = $request->input('picking_status');

        // Build query for picklist data
        $query = $this->orderDetail
            ->select(
                'order_details.product_id',
                'products.name',
                'products.weight',
                'stores.route_name',
                'stores.store_name',
                DB::raw('SUM(order_details.quantity) as total_quantity'),
                DB::raw('SUM(order_details.quantity * products.weight) as total_weight')
            )
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->leftJoin('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.branch_id', auth('branch')->id());

        // Apply filters (same as index)
        if ($startDate && $endDate) {
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('orders.date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('orders.date', '<=', $endDate);
        }

        if ($storeId) {
            $query->where('orders.store_id', $storeId);
        }

        if ($routeName) {
            $query->where('stores.route_name', $routeName);
        }

        if ($pickingStatus) {
            if ($pickingStatus === 'picked') {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('order_picking_items')
                        ->whereColumn('order_picking_items.order_id', 'orders.id')
                        ->whereColumn('order_picking_items.order_detail_id', 'order_details.id')
                        ->where('order_picking_items.status', 'picked');
                });
            } elseif ($pickingStatus === 'non_picked') {
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('order_picking_items')
                        ->whereColumn('order_picking_items.order_id', 'orders.id')
                        ->whereColumn('order_picking_items.order_detail_id', 'order_details.id')
                        ->where('order_picking_items.status', 'picked');
                });
            }
        }

        // Group by product and route
        $picklistData = $query
            ->groupBy('order_details.product_id', 'products.name', 'products.weight', 'stores.route_name', 'stores.store_name')
            ->orderBy('stores.route_name')
            ->orderBy('products.name')
            ->get();

        // Prepare data for Excel export
        $storage = [];
        $currentRoute = null;
        
        foreach ($picklistData as $item) {
            // Add route separator when route changes
            if ($currentRoute !== $item->route_name) {
                if ($currentRoute !== null) {
                    // Add route total row
                    $storage[] = [
                        'Route' => 'TOTAL FOR ROUTE: ' . $currentRoute,
                        'Product Name' => '',
                        'Unit Weight (kg)' => '',
                        'Quantity' => '',
                        'Total Weight (kg)' => $picklistData
                            ->where('route_name', $currentRoute)
                            ->sum('total_weight'),
                    ];
                    // Add empty row
                    $storage[] = [
                        'Route' => '',
                        'Product Name' => '',
                        'Unit Weight (kg)' => '',
                        'Quantity' => '',
                        'Total Weight (kg)' => '',
                    ];
                }
                $currentRoute = $item->route_name;
            }

            $storage[] = [
                'Route' => $item->route_name ?? 'N/A',
                'Product Name' => $item->name,
                'Unit Weight (kg)' => $item->weight ?? 0,
                'Quantity' => $item->total_quantity,
                'Total Weight (kg)' => $item->total_weight,
            ];
        }

        // Add final route total
        if ($currentRoute !== null) {
            $storage[] = [
                'Route' => 'TOTAL FOR ROUTE: ' . $currentRoute,
                'Product Name' => '',
                'Unit Weight (kg)' => '',
                'Quantity' => '',
                'Total Weight (kg)' => $picklistData
                    ->where('route_name', $currentRoute)
                    ->sum('total_weight'),
            ];
        }

        return (new FastExcel($storage))->download('picklist_' . date('Y-m-d_H-i-s') . '.xlsx');
    }
}
