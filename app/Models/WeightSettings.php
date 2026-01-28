<?php

namespace App\Models;

use App\Model\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'key',
        'value',
        'type'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }
}
