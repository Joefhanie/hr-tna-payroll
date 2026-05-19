<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'is_night_shift',
        'days_of_week',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'is_night_shift' => 'boolean',
        'is_active' => 'boolean',
    ];
}
