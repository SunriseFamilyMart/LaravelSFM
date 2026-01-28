<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTripReassignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_trip_id',
        'from_delivery_man_id',
        'to_delivery_man_id',
        'reassigned_by_admin_id',
        'from_status',
        'to_status',
        'reason',
    ];

    public function trip()
    {
        return $this->belongsTo(DeliveryTrip::class, 'delivery_trip_id');
    }

    public function fromDeliveryMan()
    {
        return $this->belongsTo(\App\Model\DeliveryMan::class, 'from_delivery_man_id');
    }

    public function toDeliveryMan()
    {
        return $this->belongsTo(\App\Model\DeliveryMan::class, 'to_delivery_man_id');
    }

    public function admin()
    {
        return $this->belongsTo(\App\Model\Admin::class, 'reassigned_by_admin_id');
    }
}
