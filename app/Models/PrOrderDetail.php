<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrOrderDetail extends Model
{
    protected $fillable = [
        'product_id','pr_order_id','order_user','price',
        'product_details','variation','discount_on_product',
        'discount_type','quantity','tax_amount','variant',
        'unit','is_stock_decreased','time_slot_id',
        'delivery_date','vat_status','invoice_number','expected_date'
    ];

    protected $casts = [
        'product_details' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(PrOrder::class, 'pr_order_id');
    }
}
