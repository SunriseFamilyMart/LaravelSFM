<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrOrderPayment extends Model
{
    protected $fillable = [
        'pr_order_id','payment_method','amount',
        'payment_date','transaction_id','first_payment',
        'second_payment','first_payment_date',
        'second_payment_date','payment_status'
    ];

    public function order()
    {
        return $this->belongsTo(PrOrder::class, 'pr_order_id');
    }
}
