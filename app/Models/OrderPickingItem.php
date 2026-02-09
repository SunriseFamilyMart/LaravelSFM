<?php

namespace App\Models;

use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPickingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_detail_id',
        'product_id',
        'ordered_qty',
        'picked_qty',
        'missing_qty',
        'missing_reason',
        'picked_by',
        'picked_at',
        'status',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'order_detail_id' => 'integer',
        'product_id' => 'integer',
        'ordered_qty' => 'integer',
        'picked_qty' => 'integer',
        'missing_qty' => 'integer',
        'picked_by' => 'integer',
        'picked_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function picker()
    {
        return $this->belongsTo(User::class, 'picked_by');
    }
}
