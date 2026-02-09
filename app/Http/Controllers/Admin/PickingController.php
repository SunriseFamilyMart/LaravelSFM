<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\DeliveryMan;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Models\DeliveryTrip;
use App\Models\OrderPickingItem;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use function App\CentralLogics\translate;

class PickingController extends Controller
{
    public function __construct(
        private Order $order,
        private OrderDetail $orderDetail,
        private OrderPickingItem $pickingItem,
        private Branch $branch,
        private DeliveryMan $deliveryMan
    ) {
    }

    /**
     * Display all orders with status confirmed or picking
     *
     * @param Request $request
     * @return Factory|View|Application
     */
    public function index(Request $request): View|Factory|Application
    {
        $queryParam = [];
        $search = $request['search'] ?? null;
        $branchId = $request['branch_id'] ?? 'all';

        $branches = $this->branch->all();
        $deliveryMen = $this->deliveryMan->all();

        $query = $this->order->with(['customer', 'branch', 'details', 'pickingItems'])
            ->whereIn('order_status', ['confirmed', 'picking'])
            ->notPos();

        // Branch filter
        if ($branchId && $branchId != 'all') {
            $query->where('branch_id', $branchId);
        }

        // Search filter
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%");
                }
            });
            $queryParam['search'] = $search;
        }

        $queryParam['branch_id'] = $branchId;

        $orders = $query->orderBy('id', 'desc')
            ->paginate(Helpers::getPagination())
            ->appends($queryParam);

        return view('admin-views.picking.index', compact('orders', 'branches', 'deliveryMen', 'search', 'branchId'));
    }

    /**
     * Show a single order's items for picking
     *
     * @param $order_id
     * @return Factory|View|Application|RedirectResponse
     */
    public function show($order_id): View|Factory|Application|RedirectResponse
    {
        $order = $this->order->with(['details.product', 'pickingItems', 'customer', 'branch'])
            ->find($order_id);

        if (!$order) {
            Toastr::error(translate('Order not found'));
            return redirect()->route('admin.picking.index');
        }

        // If order status is confirmed, change it to picking and create picking items
        if ($order->order_status === 'confirmed') {
            $order->order_status = 'picking';
            $order->save();

            // Create picking items for each order detail
            foreach ($order->details as $detail) {
                OrderPickingItem::create([
                    'order_id' => $order->id,
                    'order_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'ordered_qty' => $detail->quantity,
                    'picked_qty' => 0,
                    'missing_qty' => 0,
                    'status' => 'pending',
                ]);
            }

            // Reload picking items
            $order->load('pickingItems');
        }

        return view('admin-views.picking.show', compact('order'));
    }

    /**
     * AJAX endpoint to pick a single item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pickItem(Request $request): JsonResponse
    {
        $request->validate([
            'picking_item_id' => 'required|integer',
            'picked_qty' => 'required|integer|min:0',
            'missing_reason' => 'nullable|in:out_of_stock,damaged,expired,not_found',
        ]);

        $pickingItem = $this->pickingItem->find($request->picking_item_id);

        if (!$pickingItem) {
            return response()->json(['success' => false, 'message' => translate('Picking item not found')], 404);
        }

        // Validate picked_qty
        if ($request->picked_qty > $pickingItem->ordered_qty) {
            return response()->json([
                'success' => false,
                'message' => translate('Picked quantity cannot exceed ordered quantity')
            ], 400);
        }

        // Calculate missing_qty
        $missingQty = $pickingItem->ordered_qty - $request->picked_qty;

        // Require missing_reason if missing_qty > 0
        if ($missingQty > 0 && !$request->missing_reason) {
            return response()->json([
                'success' => false,
                'message' => translate('Missing reason is required when items are missing')
            ], 400);
        }

        // Set status
        if ($request->picked_qty == $pickingItem->ordered_qty) {
            $status = 'picked';
        } elseif ($request->picked_qty == 0) {
            $status = 'missing';
        } else {
            $status = 'partial';
        }

        // Update picking item
        $pickingItem->picked_qty = $request->picked_qty;
        $pickingItem->missing_qty = $missingQty;
        $pickingItem->missing_reason = $missingQty > 0 ? $request->missing_reason : null;
        $pickingItem->status = $status;
        $pickingItem->picked_by = auth()->id();
        $pickingItem->picked_at = now();
        $pickingItem->save();

        return response()->json([
            'success' => true,
            'message' => translate('Item picked successfully'),
            'data' => $pickingItem
        ]);
    }

    /**
     * Complete the picking process
     *
     * @param $order_id
     * @return RedirectResponse
     */
    public function completePicking($order_id): RedirectResponse
    {
        $order = $this->order->with(['pickingItems', 'details'])->find($order_id);

        if (!$order) {
            Toastr::error(translate('Order not found'));
            return redirect()->route('admin.picking.index');
        }

        // Check if all items are picked (not pending)
        $pendingItems = $order->pickingItems->where('status', 'pending');
        if ($pendingItems->count() > 0) {
            Toastr::error(translate('All items must be picked before completing'));
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            // Recalculate order amount based on picked quantities
            $newOrderAmount = 0;

            foreach ($order->pickingItems as $pickingItem) {
                // Find corresponding order_detail
                $orderDetail = $order->details->where('id', $pickingItem->order_detail_id)->first();
                
                if ($orderDetail) {
                    // Update the order_detail quantity to picked_qty
                    $orderDetail->quantity = $pickingItem->picked_qty;
                    $orderDetail->save();

                    // Calculate new line total (picked_qty * price)
                    $lineTotal = $pickingItem->picked_qty * $orderDetail->price;
                    $newOrderAmount += $lineTotal;
                }
            }

            // Update order amount
            $order->order_amount = $newOrderAmount;
            $order->order_status = 'processing';
            $order->save();

            DB::commit();

            Toastr::success(translate('Picking completed successfully'));
            return redirect()->route('admin.picking.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to complete picking: ') . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Bulk assign delivery man to orders
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkAssignDeliveryMan(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer',
            'delivery_man_id' => 'required|integer',
        ]);

        $deliveryManId = $request->delivery_man_id;

        // Check if delivery man has a trip for today
        $trip = DeliveryTrip::where('delivery_man_id', $deliveryManId)
            ->whereDate('created_at', now()->toDateString())
            ->first();

        if (!$trip) {
            // Create new trip
            $tripNumber = 'TRIP-' . strtoupper(Str::random(6));
            $trip = DeliveryTrip::create([
                'trip_number' => $tripNumber,
                'delivery_man_id' => $deliveryManId,
                'order_ids' => [],
            ]);
        }

        DB::beginTransaction();
        try {
            foreach ($request->order_ids as $orderId) {
                $order = $this->order->find($orderId);
                
                if ($order) {
                    // Assign delivery man
                    $order->delivery_man_id = $deliveryManId;
                    $order->trip_number = $trip->trip_number;
                    
                    // Update status to out_for_delivery if currently processing
                    if ($order->order_status === 'processing') {
                        $order->order_status = 'out_for_delivery';
                    }
                    
                    $order->save();

                    // Add order to trip
                    $orderIds = $trip->order_ids ?? [];
                    if (!in_array($orderId, $orderIds)) {
                        $orderIds[] = $orderId;
                        $trip->order_ids = $orderIds;
                    }
                }
            }

            $trip->save();
            DB::commit();

            Toastr::success(translate('Delivery man assigned successfully'));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to assign delivery man: ') . $e->getMessage());
            return redirect()->back();
        }
    }
}
