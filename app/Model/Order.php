<?php

namespace App\Model;

use App\Models\GuestUser;
use App\Models\OfflinePayment;
use App\Models\OrderArea;
use App\Models\OrderImage;
use App\User;
use App\Models\CreditNote;
use App\Models\SalesPerson;
use App\Models\PaymentLedger;
use App\Models\PaymentAllocation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Store;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'is_guest',
        'time_slot_id',
        'delivery_address_id',
        'delivery_man_id',
        'coupon_id',
        'order_amount',
        'coupon_discount_amount',
        'payment_status',
        'order_status',
        'total_tax_amount',
        'payment_method',
        'checked',
        'delivery_charge',
        'order_type',
        'branch_id',
        'date',
        'delivery_date',
        'extra_discount',
        'sales_person_id',
        'store_id',   // ✅ Add this

    ];

    protected $casts = [
        'order_amount' => 'float',
        'checked' => 'integer',
        'branch_id' => 'integer',
        'time_slot_id' => 'integer',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'delivery_address_id' => 'integer',
        'delivery_man_id' => 'integer',
        'delivery_charge' => 'float',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'delivery_address' => 'array',
        'delivery_date' => 'date',
        'free_delivery_amount' => 'float',
    ];

    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function delivery_man(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function time_slot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function delivery_address(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function scopePos($query)
    {
        return $query->where('order_type', '=', 'pos');
    }

    public function scopeNotPos($query)
    {
        return $query->where('order_type', '!=', 'pos');
    }

    public function coupon(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    public function guest()
    {
        return $this->belongsTo(GuestUser::class, 'user_id');
    }

    public function offline_payment()
    {
        return $this->hasOne(OfflinePayment::class, 'order_id');
    }



    public function order_image()
    {
        return $this->hasMany(OrderImage::class, 'order_id');
    }

    public function order_area()
    {
        return $this->hasOne(OrderArea::class, 'order_id');
    }

    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Payment ledgers associated with this order
     */
    public function paymentLedgers()
    {
        return $this->hasMany(PaymentLedger::class, 'order_id');
    }

    /**
     * Payment allocations for this order
     */
    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class, 'order_id');
    }


    public function editLogs()
    {
        return $this->hasMany(OrderEditLog::class, 'order_id', 'id')
            ->orderBy('id', 'DESC');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

  

public function creditNotes()
{
    return $this->hasMany(CreditNote::class, 'order_id');
}

    public function pickingItems()
    {
        return $this->hasMany(\App\Models\OrderPickingItem::class);
    }

}
