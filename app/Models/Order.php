<?php

namespace App\Models;

use App\Model\DeliveryMan;
use App\Model\OrderDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Table name (optional if follows Laravel naming conventions)
     */
    protected $table = 'orders';

    /**
     * Primary key
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'int';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'user_id',
        'store_id',
        'sales_person_id',
        'is_guest',
        'order_amount',
        'coupon_discount_amount',
        'coupon_discount_title',
        'payment_status',
        'order_status',
        'total_tax_amount',
        'payment_method',
        'transaction_reference',
        'delivery_address_id',
        'checked',
        'delivery_man_id',
        'trip_number',
        'delivery_charge',
        'order_note',
        'coupon_code',
        'order_type',
        'branch_id',
        'time_slot_id',
        'date',
        'delivery_date',
        'callback',
        'extra_discount',
        'delivery_address',
        'payment_by',
        'payment_note',
        'free_delivery_amount',
        'weight_charge_amount',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'is_guest' => 'boolean',
        'checked' => 'boolean',
        'order_amount' => 'float',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'delivery_charge' => 'float',
        'extra_discount' => 'float',
        'free_delivery_amount' => 'float',
        'weight_charge_amount' => 'float',
        'delivery_address' => 'array', // since it’s a JSON field
        'date' => 'date',
        'delivery_date' => 'date',
    ];

    /**
     * Relationships
     */

    // 🧍 Belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 🏪 Belongs to a store (if applicable)
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    // 🚚 Belongs to a delivery man
    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    // 🧾 Has many order details
    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    // 🚗 Belongs to a delivery trip (via trip_number or delivery_man_id)
    public function trip()
    {
        return $this->belongsTo(DeliveryTrip::class, 'trip_number', 'trip_number');
    }

    // 💳 Scope to filter paid orders
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    // 🕒 Scope to filter by status
    public function scopeStatus($query, $status)
    {
        return $query->where('order_status', $status);
    }

    public function creditNotes()
{
    return $this->hasMany(\App\Models\CreditNote::class, 'order_id');
}

}
