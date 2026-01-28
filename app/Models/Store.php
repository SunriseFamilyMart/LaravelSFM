<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'store_name',
        'customer_name',
        'address',
        'phone_number',
        'alternate_number',
        'landmark',
        'latitude',
        'longitude',
        'sales_person_id',
        'store_photo',
        'gst_number', // 👈 add this

        // ✅ Store self-registration + approval
        'registration_source', // 'sales_person' | 'self'
        'approval_status',     // 'pending' | 'approved' | 'rejected'
        'can_login',           // bool
        'password',            // hashed password for store login
        'auth_token',          // store auth token
        'approved_by',
        'approved_at',


    ];

    /**
     * Cast DB types so strict checks behave correctly.
     * (can_login is stored as tinyint(1) in MySQL)
     */
    protected $casts = [
        'can_login' => 'boolean',
        'approved_at' => 'datetime',
    ];


    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id')->withDefault();
    }

}
