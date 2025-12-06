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


    ];


    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id')->withDefault();
    }

}
