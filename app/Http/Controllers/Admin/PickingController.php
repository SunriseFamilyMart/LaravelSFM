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
        $from = $request['from'] ?? null;
        $to = $request['to'] ?? null;

        $branches = $this->branch->all();
        $deliveryMen = $this->deliveryMan->all();

        $query = $this->order->with(['customer', 'branch', 'details', 'pickingItems'])
            ->whereIn('order_status', ['confirmed', 'picking', 'processing', 'packaging']);

        // Date filter
        if ($request->filled('from')) {
            // Validate date format
            try {
                $fromDate = \Carbon\Carbon::parse($request->from)->format('Y-m-d');
            } catch (\Exception $e) {
                $fromDate = null;
            }
            
            if ($fromDate) {
                if ($request->filled('to')) {
                    // Both dates provided - use between
                    try {
                        $toDate = \Carbon\Carbon::parse($request->to)->format('Y-m-d');
                        $query->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                        $queryParam['to'] = $to;
                    } catch (\Exception $e) {
                        // Invalid to date, just use from
                        $query->where('created_at', '>=', $fromDate . ' 00:00:00');
                    }
                } else {
                    // Only from date - filter from this date onwards
                    $query->where('created_at', '>=', $fromDate . ' 00:00:00');
                }
                $queryParam['from'] = $from;
            }
        } elseif ($request->filled('to')) {
            // Only to date - filter up to this date
            try {
                $toDate = \Carbon\Carbon::parse($request->to)->format('Y-m-d');
                $query->where('created_at', '<=', $toDate . ' 23:59:59');
                $queryParam['to'] = $to;
            } catch (\Exception $e) {
                // Invalid date, skip filter
            }
        }

        // Branch filter
        if ($branchId && $branchId != 'all') {
            $query->where('branch_id', $branchId);
        }

        // Search filter
        if ($request->filled('search')) {
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

        // Calculate "picked" status for each order
        foreach ($orders as $order) {
            $order->all_picked = $order->pickingItems->isNotEmpty() && 
                                 $order->pickingItems->where('status', 'pending')->count() == 0;
        }

        return view('admin-views.picking.index', compact('orders', 'branches', 'deliveryMen', 'search', 'branchId', 'from', 'to'));
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

        // Auto-create picking items if the order doesn't have any yet
        if ($order->pickingItems->isEmpty()) {
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

        $deliveryMen = $this->deliveryMan->all();

        return view('admin-views.picking.show', compact('order', 'deliveryMen'));
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
     * @param Request $request
     * @param $order_id
     * @return RedirectResponse
     */
    public function completePicking(Request $request, $order_id): RedirectResponse
    {
        $order = $this->order->with(['pickingItems', 'details'])->find($order_id);

        if (!$order) {
            Toastr::error(translate('Order not found'));
            return redirect()->route('admin.picking.index');
        }

        DB::beginTransaction();
        try {
            // Get missing items data from request
            $missingItems = $request->input('missing_items', []);
            $missingQty = $request->input('missing_qty', []);
            $missingReason = $request->input('missing_reason', []);

            // Process all picking items
            foreach ($order->pickingItems as $pickingItem) {
                // Check if this item is marked as missing
                if (in_array($pickingItem->id, $missingItems)) {
                    // Item is marked as missing
                    $itemMissingQty = isset($missingQty[$pickingItem->id]) ? (int)$missingQty[$pickingItem->id] : 1;
                    $itemMissingReason = $missingReason[$pickingItem->id] ?? null;

                    // Validate missing qty - must be at least 1 and not exceed ordered qty
                    if ($itemMissingQty < 1) {
                        $itemMissingQty = 1;
                    }
                    if ($itemMissingQty > $pickingItem->ordered_qty) {
                        $itemMissingQty = $pickingItem->ordered_qty;
                    }

                    $itemPickedQty = $pickingItem->ordered_qty - $itemMissingQty;

                    // Determine status
                    if ($itemPickedQty == 0) {
                        $status = 'missing';
                    } else {
                        $status = 'partial';
                    }

                    $pickingItem->picked_qty = $itemPickedQty;
                    $pickingItem->missing_qty = $itemMissingQty;
                    $pickingItem->missing_reason = $itemMissingReason;
                    $pickingItem->status = $status;
                } else {
                    // Item is NOT marked as missing - auto picked fully
                    $pickingItem->picked_qty = $pickingItem->ordered_qty;
                    $pickingItem->missing_qty = 0;
                    $pickingItem->missing_reason = null;
                    $pickingItem->status = 'picked';
                }

                // Set picked_by and picked_at
                $pickingItem->picked_by = auth()->id();
                $pickingItem->picked_at = now();
                $pickingItem->save();
            }

            // Recalculate order amount based on picked quantities
            $newOrderAmount = 0;
            $newTotalTax = 0;

            foreach ($order->pickingItems as $pickingItem) {
                // Find corresponding order_detail
                $orderDetail = $order->details->where('id', $pickingItem->order_detail_id)->first();
                
                if ($orderDetail) {
                    // Get original values (either from picking item if already saved, or from current order_detail)
                    $originalPrice = $pickingItem->original_price ?? $orderDetail->price;
                    $originalTaxAmount = $pickingItem->original_tax_amount ?? $orderDetail->tax_amount;
                    $originalDiscount = $pickingItem->original_discount ?? $orderDetail->discount_on_product;
                    
                    // Save original values to picking item for audit trail (if not already saved)
                    if (!$pickingItem->original_price) {
                        $pickingItem->original_price = $originalPrice;
                        $pickingItem->original_tax_amount = $originalTaxAmount;
                        $pickingItem->original_discount = $originalDiscount;
                        $pickingItem->save();
                    }

                    // Calculate proportions based on picked vs ordered quantity
                    if ($pickingItem->ordered_qty > 0) {
                        $proportion = $pickingItem->picked_qty / $pickingItem->ordered_qty;
                    } else {
                        $proportion = 0;
                    }

                    // Recalculate tax proportionally using original tax amount
                    $newTaxAmount = $originalTaxAmount * $proportion;
                    $orderDetail->tax_amount = $newTaxAmount;
                    $newTotalTax += $newTaxAmount;

                    // Recalculate discount proportionally using original discount
                    $newDiscount = $originalDiscount * $proportion;
                    $orderDetail->discount_on_product = $newDiscount;

                    // Update the order_detail quantity to picked_qty
                    $orderDetail->quantity = $pickingItem->picked_qty;
                    $orderDetail->save();

                    // Calculate new line total: (picked_qty × price) - discount + tax
                    $lineTotal = ($pickingItem->picked_qty * $originalPrice) - $newDiscount + $newTaxAmount;
                    $newOrderAmount += $lineTotal;
                }
            }

            // Update order amount, total tax, and status
            $order->order_amount = $newOrderAmount;
            $order->total_tax_amount = $newTotalTax;
            $order->order_status = 'processing';
            $order->save();

            DB::commit();

            Toastr::success(translate('Picking completed successfully'));
            return redirect()->route('admin.picking.show', ['order_id' => $order->id]);
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
