<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\OrderEditLog;

class OrderDetail extends Model
{
    protected $fillable = [
        'product_id',
        'order_id',
        'price',
        'product_details',
        'variation',
        'discount_on_product',
        'discount_type',
        'quantity',
        'total_quantity',
        'tax_amount',
        'variant',
        'unit',
        'is_stock_decreased',
        'time_slot_id',
        'delivery_date',
        'vat_status',
          'invoice_number',
         'expected_date',
         'order_user',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'order_id' => 'integer',
        'price' => 'float',
        'discount_on_product' => 'float',
        'quantity' => 'integer',
        'tax_amount' => 'float',
        'is_stock_decreased' => 'boolean',
        'time_slot_id' => 'integer',
        'product_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',


    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }
public function editLogs()
{
    return $this->hasMany(OrderEditLog::class, 'order_detail_id');
}
}


