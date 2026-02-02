<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GstLedger extends Model
{
    protected $fillable = [
        'branch',
        'type',
        'taxable_amount',
        'gst_amount',
        'reference_type',
        'reference_id'
    ];
}
