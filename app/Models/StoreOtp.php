<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreOtp extends Model
{
    protected $table = 'store_otps';

    protected $fillable = [
        'phone_number',
        'otp',
        'expires_at',
    ];
}
