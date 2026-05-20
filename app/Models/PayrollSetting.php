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
        'late_grace_period_minutes',
        'late_11_15_deduction_hours',
        'late_16_30_deduction_hours',
        'late_31_60_deduction_hours',
        'late_61_plus_deduction_hours',
    ];

    protected $casts = [
        'attendance_overtime_multiplier' => 'decimal:2',
        'attendance_night_differential_multiplier' => 'decimal:2',
        'attendance_late_deduction_multiplier' => 'decimal:2',
        'attendance_undertime_deduction_multiplier' => 'decimal:2',
        'attendance_absence_deduction_multiplier' => 'decimal:2',
        'late_grace_period_minutes' => 'integer',
        'late_11_15_deduction_hours' => 'decimal:2',
        'late_16_30_deduction_hours' => 'decimal:2',
        'late_31_60_deduction_hours' => 'decimal:2',
        'late_61_plus_deduction_hours' => 'decimal:2',
    ];
}
