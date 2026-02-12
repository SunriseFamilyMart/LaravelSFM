<?php

/**
 * @deprecated Use App\Models\PaymentLedger and App\Models\PaymentAllocation instead
 * 
 * This model is kept for backward compatibility with existing data in the order_partial_payments table.
 * DO NOT use this model in new code. All new payment tracking should use PaymentLedger and PaymentAllocation.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPartialPayment extends Model
{
    use HasFactory;

    protected $casts = [
        'order_id' => 'integer',
        'paid_amount' => 'float',
        'due_amount' => 'float',
    ];

    protected $fillable = [
        'order_id',
        'paid_with',
        'paid_amount',
        'due_amount',
    ];
}
