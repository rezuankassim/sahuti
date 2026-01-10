<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingState extends Model
{
    public const STEP_NAME = 'name';

    public const STEP_SERVICES = 'services';

    public const STEP_AREAS = 'areas';

    public const STEP_HOURS = 'hours';

    public const STEP_BOOKING = 'booking';

    public const STEP_CONFIRM = 'confirm';

    protected $fillable = [
        'phone_number',
        'current_step',
        'collected_data',
        'is_complete',
    ];

    protected $casts = [
        'collected_data' => 'array',
        'is_complete' => 'boolean',
    ];
}
