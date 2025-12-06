<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderEditLog extends Model
{
    protected $fillable = [
        'order_id',
        'order_detail_id',
        'delivery_man_id',
        'reason',
        'old_quantity',
        'new_quantity',
        'old_price',
        'new_price',
        'photo',
    ];

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
        return $this->belongsTo(\App\Model\Order::class, 'order_id');
    }

}
