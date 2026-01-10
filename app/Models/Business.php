<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'phone_number',
        'name',
        'services',
        'areas',
        'operating_hours',
        'booking_method',
        'profile_data',
        'is_onboarded',
    ];

    protected $casts = [
        'services' => 'array',
        'areas' => 'array',
        'operating_hours' => 'array',
        'profile_data' => 'array',
        'is_onboarded' => 'boolean',
    ];
}
