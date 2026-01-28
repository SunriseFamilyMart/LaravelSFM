<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderEditLog extends Model
{
    protected $fillable = [
        'order_id',
        'order_detail_id',
        'delivery_man_id',
        'action',
        'edited_by_type',
        'edited_by_id',
        'reason',
        'old_quantity',
        'new_quantity',
        'old_price',
        'new_price',
        'unit_price',
        'discount_per_unit',
        'tax_per_unit',
        'price_difference',
        'quantity_difference',
        'return_type',
        'photo',
        'notes',
        'order_amount_before',
        'order_amount_after',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'order_detail_id' => 'integer',
        'delivery_man_id' => 'integer',
        'edited_by_id' => 'integer',
        'old_quantity' => 'integer',
        'new_quantity' => 'integer',
        'old_price' => 'float',
        'new_price' => 'float',
        'unit_price' => 'float',
        'discount_per_unit' => 'float',
        'tax_per_unit' => 'float',
        'price_difference' => 'float',
        'quantity_difference' => 'integer',
        'order_amount_before' => 'float',
        'order_amount_after' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Action type constants
    const ACTION_QUANTITY_INCREASE = 'quantity_increase';
    const ACTION_QUANTITY_DECREASE = 'quantity_decrease';
    const ACTION_PARTIAL_RETURN = 'partial_return';
    const ACTION_FULL_RETURN = 'full_return';
    const ACTION_PRICE_ADJUSTMENT = 'price_adjustment';
    const ACTION_ITEM_REMOVED = 'item_removed';
    const ACTION_ITEM_ADDED = 'item_added';

    // Edited by type constants
    const EDITED_BY_ADMIN = 'admin';
    const EDITED_BY_DELIVERY_MAN = 'delivery_man';
    const EDITED_BY_SALES_PERSON = 'sales_person';
    const EDITED_BY_SYSTEM = 'system';

    // Return type constants
    const RETURN_PARTIAL = 'partial';
    const RETURN_FULL = 'full';
    const RETURN_NONE = 'none';

    /**
     * Relationships
     */
    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function editor()
    {
        if ($this->edited_by_type === self::EDITED_BY_ADMIN) {
            return $this->belongsTo(Admin::class, 'edited_by_id');
        } elseif ($this->edited_by_type === self::EDITED_BY_DELIVERY_MAN) {
            return $this->belongsTo(DeliveryMan::class, 'edited_by_id');
        } elseif ($this->edited_by_type === self::EDITED_BY_SALES_PERSON) {
            return $this->belongsTo(\App\Models\SalesPerson::class, 'edited_by_id');
        }
        return null;
    }

    /**
     * Accessors
     */
    public function getActionLabelAttribute()
    {
        $labels = [
            self::ACTION_QUANTITY_INCREASE => 'Quantity Increased',
            self::ACTION_QUANTITY_DECREASE => 'Quantity Decreased',
            self::ACTION_PARTIAL_RETURN => 'Partial Return',
            self::ACTION_FULL_RETURN => 'Full Return',
            self::ACTION_PRICE_ADJUSTMENT => 'Price Adjusted',
            self::ACTION_ITEM_REMOVED => 'Item Removed',
            self::ACTION_ITEM_ADDED => 'Item Added',
        ];
        return $labels[$this->action] ?? ucwords(str_replace('_', ' ', $this->action ?? 'Updated'));
    }

    public function getActionBadgeClassAttribute()
    {
        $classes = [
            self::ACTION_QUANTITY_INCREASE => 'badge-soft-success',
            self::ACTION_QUANTITY_DECREASE => 'badge-soft-warning',
            self::ACTION_PARTIAL_RETURN => 'badge-soft-info',
            self::ACTION_FULL_RETURN => 'badge-soft-danger',
            self::ACTION_PRICE_ADJUSTMENT => 'badge-soft-primary',
            self::ACTION_ITEM_REMOVED => 'badge-soft-dark',
            self::ACTION_ITEM_ADDED => 'badge-soft-success',
        ];
        return $classes[$this->action] ?? 'badge-soft-secondary';
    }

    public function getActionIconAttribute()
    {
        $icons = [
            self::ACTION_QUANTITY_INCREASE => 'tio-trending-up',
            self::ACTION_QUANTITY_DECREASE => 'tio-trending-down',
            self::ACTION_PARTIAL_RETURN => 'tio-replay',
            self::ACTION_FULL_RETURN => 'tio-clear-circle',
            self::ACTION_PRICE_ADJUSTMENT => 'tio-dollar-outlined',
            self::ACTION_ITEM_REMOVED => 'tio-remove-from-trash',
            self::ACTION_ITEM_ADDED => 'tio-add-circle-outlined',
        ];
        return $icons[$this->action] ?? 'tio-edit';
    }

    public function getEditorNameAttribute()
    {
        if ($this->edited_by_type === self::EDITED_BY_ADMIN) {
            $admin = Admin::find($this->edited_by_id);
            return $admin ? trim($admin->f_name . ' ' . $admin->l_name) : 'Admin';
        } elseif ($this->edited_by_type === self::EDITED_BY_DELIVERY_MAN) {
            return $this->deliveryMan ? trim($this->deliveryMan->f_name . ' ' . $this->deliveryMan->l_name) : 'Delivery Person';
        } elseif ($this->edited_by_type === self::EDITED_BY_SALES_PERSON) {
            $sales = \App\Models\SalesPerson::find($this->edited_by_id);
            return $sales ? trim($sales->name) : 'Sales Person';
        }
        return 'System';
    }

    public function getEditorTypeLabelAttribute()
    {
        $labels = [
            self::EDITED_BY_ADMIN => 'Admin',
            self::EDITED_BY_DELIVERY_MAN => 'Delivery Person',
            self::EDITED_BY_SALES_PERSON => 'Sales Person',
            self::EDITED_BY_SYSTEM => 'System',
        ];
        return $labels[$this->edited_by_type] ?? 'Unknown';
    }

    public function getQuantityChangeTextAttribute()
    {
        $diff = $this->new_quantity - $this->old_quantity;
        if ($diff > 0) {
            return '+' . $diff;
        }
        return (string) $diff;
    }

    public function getPriceChangeTextAttribute()
    {
        $diff = $this->new_price - $this->old_price;
        $symbol = '₹';
        if ($diff > 0) {
            return '+' . $symbol . number_format(abs($diff), 2);
        } elseif ($diff < 0) {
            return '-' . $symbol . number_format(abs($diff), 2);
        }
        return $symbol . '0.00';
    }

    public function getIsIncreaseAttribute()
    {
        return $this->new_quantity > $this->old_quantity;
    }

    public function getIsDecreaseAttribute()
    {
        return $this->new_quantity < $this->old_quantity;
    }

    public function getIsReturnAttribute()
    {
        return in_array($this->action, [self::ACTION_PARTIAL_RETURN, self::ACTION_FULL_RETURN]) ||
               in_array($this->return_type, [self::RETURN_PARTIAL, self::RETURN_FULL]);
    }

    public function getReturnedQuantityAttribute()
    {
        return max(0, $this->old_quantity - $this->new_quantity);
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return null;
    }

    /**
     * Scopes
     */
    public function scopeReturns($query)
    {
        return $query->whereIn('action', [self::ACTION_PARTIAL_RETURN, self::ACTION_FULL_RETURN])
                     ->orWhereIn('return_type', [self::RETURN_PARTIAL, self::RETURN_FULL]);
    }

    public function scopeQuantityChanges($query)
    {
        return $query->where('old_quantity', '!=', DB::raw('new_quantity'));
    }

    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByOrderDetail($query, $orderDetailId)
    {
        return $query->where('order_detail_id', $orderDetailId);
    }

    /**
     * Helper methods
     */
    public static function determineAction($oldQty, $newQty, $reason = null)
    {
        if ($newQty == 0) {
            return self::ACTION_FULL_RETURN;
        } elseif ($newQty < $oldQty) {
            $returnKeywords = ['return', 'damage', 'defect', 'rejected', 'refused'];
            $reasonLower = strtolower($reason ?? '');
            foreach ($returnKeywords as $keyword) {
                if (strpos($reasonLower, $keyword) !== false) {
                    return self::ACTION_PARTIAL_RETURN;
                }
            }
            return self::ACTION_QUANTITY_DECREASE;
        } elseif ($newQty > $oldQty) {
            return self::ACTION_QUANTITY_INCREASE;
        }
        return self::ACTION_PRICE_ADJUSTMENT;
    }

    public static function determineReturnType($oldQty, $newQty)
    {
        if ($newQty == 0) {
            return self::RETURN_FULL;
        } elseif ($newQty < $oldQty) {
            return self::RETURN_PARTIAL;
        }
        return self::RETURN_NONE;
    }

    public static function createLog(array $data)
    {
        $data['quantity_difference'] = ($data['new_quantity'] ?? 0) - ($data['old_quantity'] ?? 0);
        $data['price_difference'] = ($data['new_price'] ?? 0) - ($data['old_price'] ?? 0);
        
        if (empty($data['action'])) {
            $data['action'] = self::determineAction(
                $data['old_quantity'] ?? 0,
                $data['new_quantity'] ?? 0,
                $data['reason'] ?? null
            );
        }
        
        if (empty($data['return_type'])) {
            $data['return_type'] = self::determineReturnType(
                $data['old_quantity'] ?? 0,
                $data['new_quantity'] ?? 0
            );
        }

        return self::create($data);
    }
}
