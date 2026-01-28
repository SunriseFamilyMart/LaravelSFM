<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * UPI Transaction Model
 * Records all UPI Intent payments
 * 
 * Status Flow:
 * INITIATED → SUCCESS_UNCONFIRMED → SETTLED
 *          → PENDING
 *          → FAILED
 *          → CANCELLED
 *          → EXPIRED
 * 
 * Supports:
 * - Delivery Man (existing) - Collects payment from customer
 * - Sales Person (NEW) - Places order on behalf of store
 * - Store (NEW) - Self ordering
 */
class UpiTransaction extends Model
{
    use HasFactory;

    protected $table = 'upi_transactions';

    protected $fillable = [
        'payment_ref',
        'order_id',
        'delivery_man_id',
        'sales_person_id',  // NEW: Added for sales person
        'store_id',
        'amount',
        'upi_id',
        'merchant_name',
        'txn_id',
        'approval_ref_no',
        'response_code',
        'upi_app',
        'status',
        'bank_reference',
        'initiated_at',
        'confirmed_at',
        'failed_at',
        'cancelled_at',
        'settled_at',
        'expires_at',
        'confirmed_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'settled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_INITIATED = 'initiated';
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS_UNCONFIRMED = 'success_unconfirmed';  // User-confirmed, not bank-confirmed
    const STATUS_SETTLED = 'settled';  // Bank-confirmed after reconciliation
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    /**
     * Relationship: Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: Delivery Man
     */
    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    /**
     * Relationship: Sales Person (NEW)
     */
    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class);
    }

    /**
     * Relationship: Store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship: Confirmed By (Delivery Man)
     */
    public function confirmedBy()
    {
        return $this->belongsTo(DeliveryMan::class, 'confirmed_by');
    }

    // =====================================================================
    // SCOPES
    // =====================================================================

    /**
     * Scope: Successful (user-confirmed)
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_SUCCESS_UNCONFIRMED, self::STATUS_SETTLED]);
    }

    /**
     * Scope: Settled (bank-confirmed)
     */
    public function scopeSettled($query)
    {
        return $query->where('status', self::STATUS_SETTLED);
    }

    /**
     * Scope: Pending settlement (needs bank reconciliation)
     */
    public function scopePendingSettlement($query)
    {
        return $query->where('status', self::STATUS_SUCCESS_UNCONFIRMED);
    }

    /**
     * Scope: Pending transactions
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_INITIATED, self::STATUS_PENDING]);
    }

    /**
     * Scope: Failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_EXPIRED]);
    }

    /**
     * Scope: By Order
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope: By Delivery Man
     */
    public function scopeByDeliveryMan($query, $deliveryManId)
    {
        return $query->where('delivery_man_id', $deliveryManId);
    }

    /**
     * Scope: By Sales Person (NEW)
     */
    public function scopeBySalesPerson($query, $salesPersonId)
    {
        return $query->where('sales_person_id', $salesPersonId);
    }

    /**
     * Scope: By Store
     */
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope: By Date Range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Today's transactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    // =====================================================================
    // STATUS CHECK METHODS
    // =====================================================================

    /**
     * Check if payment is successful (user-confirmed)
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESS_UNCONFIRMED, self::STATUS_SETTLED]);
    }

    /**
     * Check if payment is settled (bank-confirmed)
     */
    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_INITIATED, self::STATUS_PENDING]);
    }

    /**
     * Check if payment has failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_EXPIRED]);
    }

    /**
     * Check if payment has expired
     */
    public function hasExpired(): bool
    {
        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return true;
        }
        return false;
    }

    /**
     * Check if payment needs settlement
     */
    public function needsSettlement(): bool
    {
        return $this->status === self::STATUS_SUCCESS_UNCONFIRMED;
    }

    /**
     * Check if payment can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return !$this->isSuccessful() && !$this->isFailed();
    }

    // =====================================================================
    // STATUS UPDATE METHODS
    // =====================================================================

    /**
     * Mark as successful (user-confirmed)
     */
    public function markAsSuccess($txnId = null, $confirmedBy = null)
    {
        $this->update([
            'status' => self::STATUS_SUCCESS_UNCONFIRMED,
            'txn_id' => $txnId,
            'confirmed_at' => now(),
            'confirmed_by' => $confirmedBy,
        ]);
    }

    /**
     * Mark as settled (bank-confirmed)
     */
    public function markAsSettled($bankReference = null)
    {
        $this->update([
            'status' => self::STATUS_SETTLED,
            'bank_reference' => $bankReference,
            'settled_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'notes' => $reason,
        ]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Mark as expired
     */
    public function markAsExpired()
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Mark as pending
     */
    public function markAsPending($txnId = null)
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'txn_id' => $txnId,
        ]);
    }

    // =====================================================================
    // ACCESSORS (Computed Attributes)
    // =====================================================================

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_INITIATED => 'Initiated',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS_UNCONFIRMED => 'Success (Unconfirmed)',
            self::STATUS_SETTLED => 'Settled',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst($this->status ?? 'Unknown'),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SETTLED => 'green',
            self::STATUS_SUCCESS_UNCONFIRMED => 'blue',
            self::STATUS_PENDING, self::STATUS_INITIATED => 'yellow',
            self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_EXPIRED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status icon for UI
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SETTLED => 'check-circle',
            self::STATUS_SUCCESS_UNCONFIRMED => 'clock',
            self::STATUS_PENDING, self::STATUS_INITIATED => 'hourglass',
            self::STATUS_FAILED => 'x-circle',
            self::STATUS_CANCELLED => 'ban',
            self::STATUS_EXPIRED => 'clock',
            default => 'question-circle',
        };
    }

    /**
     * Check if this is a delivery man initiated transaction
     */
    public function getIsDeliveryManTransactionAttribute(): bool
    {
        return !empty($this->delivery_man_id);
    }

    /**
     * Check if this is a sales person initiated transaction (NEW)
     */
    public function getIsSalesPersonTransactionAttribute(): bool
    {
        return !empty($this->sales_person_id);
    }

    /**
     * Get initiator type
     */
    public function getInitiatorTypeAttribute(): string
    {
        if (!empty($this->delivery_man_id)) {
            return 'delivery_man';
        }
        if (!empty($this->sales_person_id)) {
            return 'sales_person';
        }
        if (str_starts_with($this->payment_ref ?? '', 'UPI-SP-')) {
            return 'sales_person';
        }
        if (str_starts_with($this->payment_ref ?? '', 'UPI-ST-')) {
            return 'store';
        }
        return 'unknown';
    }

    // =====================================================================
    // STATIC HELPERS
    // =====================================================================

    /**
     * Get all status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_INITIATED => 'Initiated',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS_UNCONFIRMED => 'Success (Unconfirmed)',
            self::STATUS_SETTLED => 'Settled',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
        ];
    }

    /**
     * Get successful status values
     */
    public static function getSuccessStatuses(): array
    {
        return [self::STATUS_SUCCESS_UNCONFIRMED, self::STATUS_SETTLED];
    }

    /**
     * Get failed status values
     */
    public static function getFailedStatuses(): array
    {
        return [self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_EXPIRED];
    }

    /**
     * Get pending status values
     */
    public static function getPendingStatuses(): array
    {
        return [self::STATUS_INITIATED, self::STATUS_PENDING];
    }

    /**
     * Find by payment reference
     */
    public static function findByPaymentRef($paymentRef)
    {
        return self::where('payment_ref', $paymentRef)->first();
    }

    /**
     * Find by transaction ID
     */
    public static function findByTxnId($txnId)
    {
        return self::where('txn_id', $txnId)->first();
    }

    /**
     * Get total collected amount for an order
     */
    public static function getTotalCollectedForOrder($orderId): float
    {
        return self::where('order_id', $orderId)
            ->successful()
            ->sum('amount');
    }

    /**
     * Get total collected amount for a store
     */
    public static function getTotalCollectedForStore($storeId): float
    {
        return self::where('store_id', $storeId)
            ->successful()
            ->sum('amount');
    }

    /**
     * Get total collected by delivery man
     */
    public static function getTotalCollectedByDeliveryMan($deliveryManId): float
    {
        return self::where('delivery_man_id', $deliveryManId)
            ->successful()
            ->sum('amount');
    }

    /**
     * Get total collected by sales person (NEW)
     */
    public static function getTotalCollectedBySalesPerson($salesPersonId): float
    {
        return self::where('sales_person_id', $salesPersonId)
            ->successful()
            ->sum('amount');
    }

    /**
     * Expire all old initiated transactions
     */
    public static function expireOldTransactions(): int
    {
        return self::whereIn('status', [self::STATUS_INITIATED, self::STATUS_PENDING])
            ->where('expires_at', '<', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }
}