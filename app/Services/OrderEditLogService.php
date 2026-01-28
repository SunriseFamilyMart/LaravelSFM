<?php

namespace App\Services;

use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\OrderEditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderEditLogService
{
    /**
     * Create an order edit log with all calculations
     *
     * @param array $data
     * @return OrderEditLog
     */
    public static function createLog(array $data): OrderEditLog
    {
        // Calculate differences
        $oldQty = (int) ($data['old_quantity'] ?? 0);
        $newQty = (int) ($data['new_quantity'] ?? 0);
        $oldPrice = (float) ($data['old_price'] ?? 0);
        $newPrice = (float) ($data['new_price'] ?? 0);

        $data['quantity_difference'] = $newQty - $oldQty;
        $data['price_difference'] = $newPrice - $oldPrice;

        // Determine action type
        if (empty($data['action'])) {
            $data['action'] = self::determineAction($oldQty, $newQty, $data['reason'] ?? null);
        }

        // Determine return type
        if (empty($data['return_type'])) {
            $data['return_type'] = self::determineReturnType($oldQty, $newQty);
        }

        // Set editor info if not provided
        if (empty($data['edited_by_type']) && empty($data['edited_by_id'])) {
            $editorInfo = self::getEditorInfo();
            $data['edited_by_type'] = $editorInfo['type'];
            $data['edited_by_id'] = $editorInfo['id'];
        }

        // Calculate unit price from order detail if not provided
        if (empty($data['unit_price']) && !empty($data['order_detail_id'])) {
            $orderDetail = OrderDetail::find($data['order_detail_id']);
            if ($orderDetail) {
                $data['unit_price'] = $orderDetail->price ?? 0;
                $data['discount_per_unit'] = $orderDetail->discount ?? 0;
                $data['tax_per_unit'] = $orderDetail->tax_amount ?? 0;
            }
        }

        // Get order amounts before/after if not provided
        if ((empty($data['order_amount_before']) || empty($data['order_amount_after'])) && !empty($data['order_id'])) {
            $order = Order::find($data['order_id']);
            if ($order) {
                $data['order_amount_before'] = $data['order_amount_before'] ?? $order->order_amount;
                // Calculate new order amount after this change
                $data['order_amount_after'] = $data['order_amount_after'] ?? 
                    ($order->order_amount + $data['price_difference']);
            }
        }

        return OrderEditLog::create($data);
    }

    /**
     * Log a quantity change
     *
     * @param int $orderId
     * @param int $orderDetailId
     * @param int $oldQty
     * @param int $newQty
     * @param string $reason
     * @param string|null $photo
     * @param array $extraData
     * @return OrderEditLog
     */
    public static function logQuantityChange(
        int $orderId,
        int $orderDetailId,
        int $oldQty,
        int $newQty,
        string $reason,
        ?string $photo = null,
        array $extraData = []
    ): OrderEditLog {
        $orderDetail = OrderDetail::find($orderDetailId);
        $unitPrice = $orderDetail->price ?? 0;

        return self::createLog(array_merge([
            'order_id' => $orderId,
            'order_detail_id' => $orderDetailId,
            'delivery_man_id' => $extraData['delivery_man_id'] ?? 0,
            'reason' => $reason,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'old_price' => $unitPrice * $oldQty,
            'new_price' => $unitPrice * $newQty,
            'unit_price' => $unitPrice,
            'photo' => $photo,
        ], $extraData));
    }

    /**
     * Log a partial return
     *
     * @param int $orderId
     * @param int $orderDetailId
     * @param int $returnedQty
     * @param string $reason
     * @param string|null $photo
     * @param array $extraData
     * @return OrderEditLog
     */
    public static function logPartialReturn(
        int $orderId,
        int $orderDetailId,
        int $returnedQty,
        string $reason,
        ?string $photo = null,
        array $extraData = []
    ): OrderEditLog {
        $orderDetail = OrderDetail::find($orderDetailId);
        $currentQty = $orderDetail->quantity ?? 0;
        $unitPrice = $orderDetail->price ?? 0;
        $newQty = max(0, $currentQty - $returnedQty);

        return self::createLog(array_merge([
            'order_id' => $orderId,
            'order_detail_id' => $orderDetailId,
            'delivery_man_id' => $extraData['delivery_man_id'] ?? 0,
            'action' => $newQty > 0 ? OrderEditLog::ACTION_PARTIAL_RETURN : OrderEditLog::ACTION_FULL_RETURN,
            'reason' => $reason,
            'old_quantity' => $currentQty,
            'new_quantity' => $newQty,
            'old_price' => $unitPrice * $currentQty,
            'new_price' => $unitPrice * $newQty,
            'unit_price' => $unitPrice,
            'return_type' => $newQty > 0 ? OrderEditLog::RETURN_PARTIAL : OrderEditLog::RETURN_FULL,
            'photo' => $photo,
        ], $extraData));
    }

    /**
     * Log a full return for all items in an order
     *
     * @param int $orderId
     * @param string $reason
     * @param array $extraData
     * @return array Array of created OrderEditLog records
     */
    public static function logFullReturn(int $orderId, string $reason, array $extraData = []): array
    {
        $order = Order::with('details')->find($orderId);
        if (!$order) {
            return [];
        }

        $logs = [];
        $deliveryManId = $extraData['delivery_man_id'] ?? ($order->delivery_man_id ?? 0);

        foreach ($order->details as $detail) {
            $logs[] = self::createLog(array_merge([
                'order_id' => $orderId,
                'order_detail_id' => $detail->id,
                'delivery_man_id' => $deliveryManId,
                'action' => OrderEditLog::ACTION_FULL_RETURN,
                'reason' => $reason,
                'old_quantity' => $detail->quantity,
                'new_quantity' => 0,
                'old_price' => $detail->price * $detail->quantity,
                'new_price' => 0,
                'unit_price' => $detail->price,
                'return_type' => OrderEditLog::RETURN_FULL,
            ], $extraData));
        }

        return $logs;
    }

    /**
     * Log a price adjustment
     *
     * @param int $orderId
     * @param int $orderDetailId
     * @param float $oldPrice
     * @param float $newPrice
     * @param string $reason
     * @param array $extraData
     * @return OrderEditLog
     */
    public static function logPriceAdjustment(
        int $orderId,
        int $orderDetailId,
        float $oldPrice,
        float $newPrice,
        string $reason,
        array $extraData = []
    ): OrderEditLog {
        $orderDetail = OrderDetail::find($orderDetailId);
        $qty = $orderDetail->quantity ?? 1;

        return self::createLog(array_merge([
            'order_id' => $orderId,
            'order_detail_id' => $orderDetailId,
            'delivery_man_id' => $extraData['delivery_man_id'] ?? 0,
            'action' => OrderEditLog::ACTION_PRICE_ADJUSTMENT,
            'reason' => $reason,
            'old_quantity' => $qty,
            'new_quantity' => $qty,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'return_type' => OrderEditLog::RETURN_NONE,
        ], $extraData));
    }

    /**
     * Determine action type based on quantity changes
     */
    public static function determineAction(int $oldQty, int $newQty, ?string $reason = null): string
    {
        if ($newQty == 0) {
            return OrderEditLog::ACTION_FULL_RETURN;
        } elseif ($newQty < $oldQty) {
            $returnKeywords = ['return', 'damage', 'defect', 'rejected', 'refused', 'spoiled', 'broken'];
            $reasonLower = strtolower($reason ?? '');
            foreach ($returnKeywords as $keyword) {
                if (strpos($reasonLower, $keyword) !== false) {
                    return OrderEditLog::ACTION_PARTIAL_RETURN;
                }
            }
            return OrderEditLog::ACTION_QUANTITY_DECREASE;
        } elseif ($newQty > $oldQty) {
            return OrderEditLog::ACTION_QUANTITY_INCREASE;
        }
        return OrderEditLog::ACTION_PRICE_ADJUSTMENT;
    }

    /**
     * Determine return type based on quantity changes
     */
    public static function determineReturnType(int $oldQty, int $newQty): string
    {
        if ($newQty == 0) {
            return OrderEditLog::RETURN_FULL;
        } elseif ($newQty < $oldQty) {
            return OrderEditLog::RETURN_PARTIAL;
        }
        return OrderEditLog::RETURN_NONE;
    }

    /**
     * Get current editor information based on authentication
     */
    public static function getEditorInfo(): array
    {
        // Check admin guard
        if (Auth::guard('admin')->check()) {
            return [
                'type' => OrderEditLog::EDITED_BY_ADMIN,
                'id' => Auth::guard('admin')->id(),
            ];
        }

        // Check delivery man (if you have a guard for it)
        // This would need to be adjusted based on your auth setup

        // Check sales person (if you have a guard for it)
        // This would need to be adjusted based on your auth setup

        // Default to system
        return [
            'type' => OrderEditLog::EDITED_BY_SYSTEM,
            'id' => null,
        ];
    }

    /**
     * Get summary statistics for order edit logs
     *
     * @param int $orderId
     * @return array
     */
    public static function getOrderLogSummary(int $orderId): array
    {
        $logs = OrderEditLog::where('order_id', $orderId)->get();

        if ($logs->isEmpty()) {
            return [
                'total_edits' => 0,
                'items_affected' => 0,
                'total_returned_qty' => 0,
                'total_price_reduction' => 0,
                'has_partial_return' => false,
                'has_full_return' => false,
                'has_quantity_increase' => false,
                'return_type' => 'none',
            ];
        }

        $totalReturned = $logs->sum(function ($log) {
            return max(0, $log->old_quantity - $log->new_quantity);
        });

        $totalPriceReduction = $logs->sum(function ($log) {
            return $log->old_price - $log->new_price;
        });

        $hasPartial = $logs->contains(function ($l) {
            return $l->action === OrderEditLog::ACTION_PARTIAL_RETURN ||
                   ($l->new_quantity > 0 && $l->new_quantity < $l->old_quantity);
        });

        $hasFull = $logs->contains(function ($l) {
            return $l->action === OrderEditLog::ACTION_FULL_RETURN || $l->new_quantity == 0;
        });

        $hasIncrease = $logs->contains(function ($l) {
            return $l->action === OrderEditLog::ACTION_QUANTITY_INCREASE || $l->new_quantity > $l->old_quantity;
        });

        // Determine overall return type
        $returnType = 'none';
        if ($hasFull && !$hasPartial) {
            $returnType = 'full';
        } elseif ($hasPartial) {
            $returnType = 'partial';
        } elseif ($hasIncrease) {
            $returnType = 'increase';
        }

        return [
            'total_edits' => $logs->count(),
            'items_affected' => $logs->unique('order_detail_id')->count(),
            'total_returned_qty' => $totalReturned,
            'total_price_reduction' => $totalPriceReduction,
            'has_partial_return' => $hasPartial,
            'has_full_return' => $hasFull,
            'has_quantity_increase' => $hasIncrease,
            'return_type' => $returnType,
        ];
    }

    /**
     * Migrate existing logs to add action and return_type fields
     * Run this once after migration to populate missing data
     */
    public static function migrateExistingLogs(): int
    {
        $updated = 0;
        
        $logs = OrderEditLog::whereNull('action')
            ->orWhere('action', '')
            ->orWhereNull('return_type')
            ->orWhere('return_type', '')
            ->get();

        foreach ($logs as $log) {
            $needsUpdate = false;
            $updateData = [];

            if (empty($log->action)) {
                $updateData['action'] = self::determineAction(
                    $log->old_quantity,
                    $log->new_quantity,
                    $log->reason
                );
                $needsUpdate = true;
            }

            if (empty($log->return_type)) {
                $updateData['return_type'] = self::determineReturnType(
                    $log->old_quantity,
                    $log->new_quantity
                );
                $needsUpdate = true;
            }

            if (empty($log->quantity_difference)) {
                $updateData['quantity_difference'] = $log->new_quantity - $log->old_quantity;
                $needsUpdate = true;
            }

            if (empty($log->price_difference)) {
                $updateData['price_difference'] = $log->new_price - $log->old_price;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $log->update($updateData);
                $updated++;
            }
        }

        return $updated;
    }
}
