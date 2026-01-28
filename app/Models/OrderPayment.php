<?php

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
        'source_order_id',      // NEW: Order where payment was originally collected
        'payment_method',
        'transaction_id',
        'first_payment',
        'second_payment',
        'first_payment_date',
        'second_payment_date',
        'payment_status',
        'amount',
        'payment_date',
        'is_adjustment',        // NEW: Whether this is an adjustment from another order
        'adjusted_from_payment_id', // NEW: Reference to original payment
    ];

    /**
     * Cast attributes
     */
    protected $casts = [
        'is_adjustment' => 'boolean',
        'amount' => 'decimal:2',
        'first_payment' => 'decimal:2',
        'second_payment' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the source order where payment was originally collected
     */
    public function sourceOrder()
    {
        return $this->belongsTo(Order::class, 'source_order_id');
    }

    /**
     * Get the original payment that caused this adjustment
     */
    public function adjustedFromPayment()
    {
        return $this->belongsTo(OrderPayment::class, 'adjusted_from_payment_id');
    }

    /**
     * Accessors / Helpers
     */

    // Check if payment is complete
    public function isComplete(): bool
    {
        return $this->payment_status === 'complete';
    }

    // Check if this is an adjustment payment
    public function isAdjustment(): bool
    {
        return $this->is_adjustment === true;
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

    // Scope for filtering direct payments (not adjustments)
    public function scopeDirect($query)
    {
        return $query->where(function($q) {
            $q->whereNull('is_adjustment')
              ->orWhere('is_adjustment', false);
        });
    }

    // Scope for filtering adjustment payments only
    public function scopeAdjustments($query)
    {
        return $query->where('is_adjustment', true);
    }

    // Scope for filtering payments from a specific source order
    public function scopeFromSourceOrder($query, $sourceOrderId)
    {
        return $query->where('source_order_id', $sourceOrderId);
    }
}