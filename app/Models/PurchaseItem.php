<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Model\Product;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'received_qty',
        'pending_qty',
        'unit_price',
        'gst_percent',
        'gst_amount',
        'total',
        'status',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_qty' => 'integer',
        'pending_qty' => 'integer',
        'unit_price' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function purchase()
    {
        return $this->belongsTo(PurchaseMaster::class, 'purchase_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryItems()
    {
        return $this->hasMany(PurchaseDeliveryItem::class, 'purchase_item_id');
    }

    // Auto-calculate pending quantity
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->pending_qty = $item->quantity - $item->received_qty;
            
            // Update item status based on received quantity
            if ($item->received_qty >= $item->quantity) {
                $item->status = 'received';
            } elseif ($item->received_qty > 0) {
                $item->status = 'partial';
            }
        });
    }
}
