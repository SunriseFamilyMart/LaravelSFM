<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreLedger extends Model
{
    protected $table = 'store_ledgers';

    protected $fillable = [
        'store_id',
        'reference_type',
        'reference_id',
        'debit',
        'credit',
        'balance_type',
        'remarks',
    ];

    protected $casts = [
        'store_id'     => 'integer',
        'reference_id' => 'integer',
        'debit'        => 'float',
        'credit'       => 'float',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Polymorphic-like helper (order / payment / credit_note / adjustment)
     * You can manually resolve based on reference_type
     */
    public function reference()
    {
        switch ($this->reference_type) {
            case 'order':
                return $this->belongsTo(Order::class, 'reference_id');
            case 'payment':
                return $this->belongsTo(PaymentLedger::class, 'reference_id');
            case 'credit_note':
                return $this->belongsTo(CreditNote::class, 'reference_id');
            default:
                return null;
        }
    }

    /* ================= SCOPES ================= */

    public function scopeReceivable($query)
    {
        return $query->where('balance_type', 'receivable');
    }

    public function scopePayable($query)
    {
        return $query->where('balance_type', 'payable');
    }

    /* ================= ACCESSORS ================= */

    /**
     * Signed balance value
     * +credit increases balance
     * -debit decreases balance
     */
    public function getNetAmountAttribute()
    {
        return ($this->credit ?? 0) - ($this->debit ?? 0);
    }
}
