<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Model\Product;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id',
        'product_id',
        'quantity',
        'price',
        'gst_percent'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }
}
