<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id',
        'product_id',
        'quantity',
        'price',
        'gst_percent'
    ];
}
