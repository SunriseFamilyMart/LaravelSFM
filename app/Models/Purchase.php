<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Model\Product;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
       'supplier_id',
        'product_id',
        'description',
        'quantity',
        'price',
        'gst',
        'amount',
        'branch',
        'batch_id',
        'invoice_number',
        'invoice'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
