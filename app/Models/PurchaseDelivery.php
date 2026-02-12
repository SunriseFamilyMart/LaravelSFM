<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\User;

class PurchaseDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'delivery_date',
        'received_by',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    // Relationships
    public function purchase()
    {
        return $this->belongsTo(PurchaseMaster::class, 'purchase_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseDeliveryItem::class, 'purchase_delivery_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
