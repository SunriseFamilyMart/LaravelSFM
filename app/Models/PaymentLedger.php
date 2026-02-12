<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLedger extends Model
{
    protected $table = 'payment_ledgers';

    protected $fillable = [
        'store_id',
        'order_id',
        'entry_type',
        'amount',
        'payment_method',
        'transaction_ref',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: A payment ledger belongs to a store
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relationship: A payment ledger may be associated with an order
     */
    public function order()
    {
        return $this->belongsTo(\App\Model\Order::class, 'order_id');
    }

    /**
     * Relationship: A payment ledger has many allocations
     */
    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_ledger_id');
    }

    /**
     * Scope: Filter by entry type (CREDIT/DEBIT)
     */
    public function scopeEntryType($query, $type)
    {
        return $query->where('entry_type', $type);
    }

    /**
     * Scope: Filter by payment method
     */
    public function scopePaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
}
