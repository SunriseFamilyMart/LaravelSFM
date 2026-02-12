<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Model\Product;

class PurchaseDeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_delivery_id',
        'purchase_item_id',
        'product_id',
        'quantity_received',
        'notes'
    ];

    protected $casts = [
        'quantity_received' => 'integer',
    ];

    // Relationships
    public function delivery()
    {
        return $this->belongsTo(PurchaseDelivery::class, 'purchase_delivery_id');
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
