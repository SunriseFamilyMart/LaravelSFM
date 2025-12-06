<?php

namespace App\Model;

use App\Models\DeliveryChargeByArea;
use App\Models\DeliveryChargeSetup;
use App\Models\WeightSettings;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Branch extends Authenticatable
{
    use Notifiable;

    protected $casts = [
        'coverage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    public function getImageFullPathAttribute(): string
    {
        $image = $this->image ?? null;
        $path = asset('public/assets/admin/img/160x160/2.png');

        if (!is_null($image) && Storage::disk('public')->exists('branch/' . $image)) {
            $path = asset('storage/app/public/branch/' . $image);
        }
        return $path;
    }

    public function delivery_charge_setup()
    {
        return $this->hasOne(DeliveryChargeSetup::class, 'branch_id', 'id');
    }
    public function delivery_charge_by_area()
    {
        return $this->hasMany(DeliveryChargeByArea::class, 'branch_id', 'id')->latest();
    }
    public function weight_settings_status()
    {
        return $this->hasOne(WeightSettings::class, 'branch_id', 'id')->where('key', 'extra_charge_on_weight');
    }
    public function weight_charge_type()
    {
        return $this->hasOne(WeightSettings::class, 'branch_id', 'id')->where('key', 'weight_charge_type');
    }

    public function weight_unit()
    {
        return $this->hasMany(WeightSettings::class, 'branch_id', 'id')->where('type', 'unit');
    }

    public function weight_range()
    {
        return $this->hasOne(WeightSettings::class, 'branch_id', 'id')->where('key', 'weight_range');
    }
}
