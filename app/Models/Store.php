<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'store_name',
        'customer_name',
        'address',
        'phone_number',
        'alternate_number',
        'landmark',
        'latitude',
        'longitude',
        'sales_person_id',
        'store_photo',
        'gst_number',
        'route_name',
        'street_address',
        'branch',
        'area',
        'city',
        'taluk',
        'district',
        'state',
        'pincode',
        'full_address',
        'full_address',
    'approval_status',      // ✅ ADD THIS
    'registration_source',  // ✅ ADD THIS
    'password',            // ✅ ADD THIS
    'can_login',           // ✅ ADD THIS
    'approved_by',         // ✅ ADD THIS
    'approved_at',         // ✅ ADD THIS
    'auth_token',  
    ];

    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id')->withDefault();
    }

    /**
     * Relationship: A store has many orders
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'store_id');
    }

    /**
     * Relationship: A store has many payment ledgers
     */
    public function paymentLedgers()
    {
        return $this->hasMany(PaymentLedger::class, 'store_id');
    }
    /**
     * Get outstanding balance for this store from store_ledgers table
     * Returns sum of (debit - credit)
     */
    public function getOutstandingBalance()
    {
        return \Illuminate\Support\Facades\DB::table('store_ledgers')
            ->where('store_id', $this->id)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance') ?? 0;
    }
}
