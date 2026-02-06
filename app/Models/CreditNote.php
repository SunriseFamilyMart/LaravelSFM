<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Model\Order;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_no',
        'order_id',
        'branch',
        'customer_id',
        'taxable_amount',
        'gst_amount',
        'total_amount',
        'reason'
    ];

      public function items()
    {
        return $this->hasMany(CreditNoteItem::class, 'credit_note_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
