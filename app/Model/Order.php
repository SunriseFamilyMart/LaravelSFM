<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Models\{
    GuestUser,
    OfflinePayment,
    OrderArea,
    OrderImage,
    Branch,
    OrderPartialPayment,
    SalesPerson,
    Store,
    OrderPayment,
    OrderDetail,
    OrderEditLog,
    DeliveryMan,
    TimeSlot,
    CustomerAddress,
    Coupon
};

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
        'store_id',
        'paid_amount',
    ];

    protected $casts = [
        'order_amount' => 'float',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'delivery_charge' => 'float',
        'paid_amount' => 'float',

        'checked' => 'integer',
        'branch_id' => 'integer',
        'time_slot_id' => 'integer',
        'delivery_address_id' => 'integer',
        'delivery_man_id' => 'integer',
        'user_id' => 'integer',

        'delivery_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guest()
    {
        return $this->belongsTo(GuestUser::class, 'user_id');
    }

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function time_slot()
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    public function delivery_address()
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    public function offline_payment()
    {
        return $this->hasOne(OfflinePayment::class, 'order_id');
    }

    public function partial_payment()
    {
        return $this->hasMany(OrderPartialPayment::class, 'order_id')->orderByDesc('id');
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function editLogs()
    {
        return $this->hasMany(OrderEditLog::class, 'order_id')->orderByDesc('id');
    }

    public function order_image()
    {
        return $this->hasMany(OrderImage::class, 'order_id');
    }

    public function order_area()
    {
        return $this->hasOne(OrderArea::class, 'order_id');
    }

    /* ================= SCOPES ================= */

    public function scopePos($query)
    {
        return $query->where('order_type', 'pos');
    }

    public function scopeNotPos($query)
    {
        return $query->where('order_type', '!=', 'pos');
    }

    public function scopePartial($query)
    {
        return $query->whereHas('partial_payment');
    }
}
