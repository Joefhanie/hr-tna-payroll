<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $table = 'payroll_settings';
    public $timestamps = true;

    protected $fillable = [
        'attendance_overtime_multiplier',
        'attendance_night_differential_multiplier',
        'attendance_late_deduction_multiplier',
        'attendance_undertime_deduction_multiplier',
        'attendance_absence_deduction_multiplier',
    ];

    protected $casts = [
        'attendance_overtime_multiplier' => 'decimal:4',
        'attendance_night_differential_multiplier' => 'decimal:4',
        'attendance_late_deduction_multiplier' => 'decimal:4',
        'attendance_undertime_deduction_multiplier' => 'decimal:4',
        'attendance_absence_deduction_multiplier' => 'decimal:4',
    ];
}
