<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class SalesPerson extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'id_proof',
        'person_photo',
        'address',
        'emergency_contact_name',
        'emergency_contact_number',
        'auth_token',
        'password',
        'branch',
    ];

    public function customers()
    {
        return $this->hasMany(User::class, 'created_by'); // users.created_by → sales_people.id
    }
}

