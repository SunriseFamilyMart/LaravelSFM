<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreVisit extends Model
{
    protected $table = 'store_visits';

    protected $fillable = [
        'store_id',
        'sales_person_id',
        'photo',
        'lat',
        'long',
        'address',
    ];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class, 'store_id');
    }

}