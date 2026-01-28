<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Model\DeliveryMan;
use App\Model\Order;

class DeliveryTrip extends Model
{
    use HasFactory;

    protected $fillable = ['trip_number', 'delivery_man_id', 'order_ids', 'status'];

    protected $casts = [
        'order_ids' => 'array',
    ];

    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function reassignments()
    {
        return $this->hasMany(DeliveryTripReassignment::class, 'delivery_trip_id');
    }

    /**
     * NOTE: order_ids is stored as JSON array, so this relation is not used by default.
     * Keep it as-is to avoid impacting existing code paths.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'id', 'order_ids');
    }
}
