<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'product_id',
        'branch',
        'type',
        'quantity',
        'remaining_qty',
        'batch_id',
        'unit_price',
        'total_value',
        'reference_type',
        'reference_id'
    ];

    public function product()
    {
        return $this->belongsTo(\App\Model\Product::class);
    }
}
