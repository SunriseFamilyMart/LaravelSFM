<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderChangeLog extends Model
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
        'is_returned',
        'credit_note_id',
        'processed_at'
    ];

    public function orderDetail()
    {
        return $this->belongsTo(\App\Model\OrderDetail::class, 'order_detail_id');
    }
}
