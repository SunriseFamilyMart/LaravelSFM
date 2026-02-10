<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'branch_id',
        'check_in',
        'check_out',
        'total_hours',
        'notes',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        if ($this->user_type === 'delivery_man') {
            return $this->belongsTo(DeliveryMan::class, 'user_id');
        }
        return $this->belongsTo(Admin::class, 'user_id');
    }

    public function getTotalHoursFormattedAttribute(): string
    {
        if (!$this->total_hours) {
            return 'N/A';
        }
        $hours = floor($this->total_hours / 60);
        $minutes = $this->total_hours % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
