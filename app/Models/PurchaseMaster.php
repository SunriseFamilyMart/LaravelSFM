<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\User;

class PurchaseMaster extends Model
{
    use HasFactory;

    protected $table = 'purchases_master';

    protected $fillable = [
        'pr_number',
        'supplier_id',
        'purchased_by',
        'purchase_date',
        'expected_delivery_date',
        'invoice_number',
        'status',
        'subtotal',
        'gst_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class, 'purchase_id');
    }

    public function deliveries()
    {
        return $this->hasMany(PurchaseDelivery::class, 'purchase_id');
    }

    public function auditLogs()
    {
        return AuditLog::where('table_name', 'purchases_master')
            ->where('record_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Auto-generate PR number on create
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->pr_number)) {
                $purchase->pr_number = self::generatePrNumber();
            }
        });
    }

    // Generate PR number in format: PR-YYYYMMDD-XXX
    public static function generatePrNumber()
    {
        $date = now()->format('Ymd');
        $prefix = "PR-{$date}-";

        // Get the last PR number for today
        $lastPr = self::where('pr_number', 'like', $prefix . '%')
            ->orderBy('pr_number', 'desc')
            ->first();

        if ($lastPr) {
            // Extract the sequential number and increment
            $lastNumber = (int) substr($lastPr->pr_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Calculate totals from items
    public function calculateTotals()
    {
        $items = $this->items;
        
        $subtotal = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $gstAmount = $items->sum('gst_amount');
        $totalAmount = $subtotal + $gstAmount;

        $this->subtotal = $subtotal;
        $this->gst_amount = $gstAmount;
        $this->total_amount = $totalAmount;
        $this->balance_amount = $totalAmount - $this->paid_amount;
        
        $this->save();
    }

    // Update payment status based on paid amount
    public function updatePaymentStatus()
    {
        if ($this->paid_amount <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }
        
        $this->save();
    }

    // Update delivery status based on received quantities
    public function updateDeliveryStatus()
    {
        $items = $this->items;
        $totalItems = $items->count();
        
        if ($totalItems == 0) {
            return;
        }

        $fullyReceivedCount = $items->filter(function ($item) {
            return $item->received_qty >= $item->quantity;
        })->count();

        $partialReceivedCount = $items->filter(function ($item) {
            return $item->received_qty > 0 && $item->received_qty < $item->quantity;
        })->count();

        if ($fullyReceivedCount == $totalItems) {
            $this->status = 'delivered';
        } elseif ($fullyReceivedCount > 0 || $partialReceivedCount > 0) {
            $this->status = 'partial_delivered';
        }
        
        $this->save();
    }
}
