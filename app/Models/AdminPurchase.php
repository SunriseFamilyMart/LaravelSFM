<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Model\Product;

class AdminPurchase extends Model
{
    use HasFactory;

    protected $table = 'adminpurchase';

    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'product_id',
        'purchased_by',
        'purchase_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'invoice_number',
        'status',
        'mrp',
        'purchase_price',
        'quantity',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_mode',
        'comments'
    ];
       public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
