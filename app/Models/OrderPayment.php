<?php

/**
 * @deprecated Use App\Models\PaymentLedger instead
 * 
 * This model is kept for backward compatibility with existing data in the order_payments table.
 * DO NOT use this model in new code. All new payment tracking should use PaymentLedger.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Model\Order;

class OrderPayment extends Model
{
    use HasFactory;

    protected $table = 'order_payments';

    protected $fillable = [
        'order_id',
        'payment_method',
        'transaction_id',
        'first_payment',
        'second_payment',
        'first_payment_date',
        'second_payment_date',
        'payment_status',
        'amount',
        'payment_date',

    ];

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Accessors / Helpers
     */

    // Check if payment is complete
    public function isComplete(): bool
    {
        return $this->payment_status === 'complete';
    }

    // Scope for filtering complete payments
    public function scopeComplete($query)
    {
        return $query->where('payment_status', 'complete');
    }

    // Scope for filtering incomplete payments
    public function scopeIncomplete($query)
    {
        return $query->where('payment_status', 'incomplete');
    }
}
