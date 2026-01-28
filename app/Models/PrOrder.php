<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrOrder extends Model
{
    protected $fillable = [
        'user_id','store_id','sales_person_id','is_guest',
        'order_amount','coupon_discount_amount','coupon_discount_title',
        'payment_status','order_status','total_tax_amount',
        'payment_method','transaction_reference','delivery_address_id',
        'checked','delivery_man_id','trip_number','delivery_charge',
        'order_note','coupon_code','order_type','branch_id',
        'time_slot_id','date','delivery_date','callback',
        'extra_discount','delivery_address','payment_by',
        'payment_note','free_delivery_amount','weight_charge_amount'
    ];

    public function details()
    {
        return $this->hasMany(PrOrderDetail::class, 'pr_order_id');
    }

    public function payments()
    {
        return $this->hasMany(PrOrderPayment::class, 'pr_order_id');
    }
}
