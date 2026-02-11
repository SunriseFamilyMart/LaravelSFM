<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BulkDiscount extends Model
{
    protected $fillable = [
        'product_id',
        'min_quantity',
        'discount_percent',
        'status',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'discount_percent' => 'float',
        'status' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
