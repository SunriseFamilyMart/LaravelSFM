<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryManOtp extends Model
{
    protected $table = 'delivery_man_otps';

    protected $fillable = [
        'phone',
        'otp',
        'expires_at'
    ];

    protected $dates = [
        'expires_at'
    ];
}
