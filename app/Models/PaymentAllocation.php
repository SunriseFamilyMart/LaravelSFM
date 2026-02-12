<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $table = 'payment_allocations';

    protected $fillable = [
        'payment_ledger_id',
        'order_id',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'float',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Relationship: An allocation belongs to a payment ledger
     */
    public function paymentLedger()
    {
        return $this->belongsTo(PaymentLedger::class, 'payment_ledger_id');
    }

    /**
     * Relationship: An allocation is associated with an order
     */
    public function order()
    {
        return $this->belongsTo(\App\Model\Order::class, 'order_id');
    }
}
