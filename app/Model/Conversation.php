<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesPerson;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'sales_person_id',
        'message',
        'reply',
        'checked',
        'image',
        'is_reply',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'sales_person_id' => 'integer',
        'checked' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'image' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesPerson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id');
    }
}
