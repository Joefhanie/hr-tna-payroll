<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LateDeduction extends Model
{
    protected $table = 'late_deductions';

    protected $fillable = [
        'time_log_id',
        'employee_id',
        'attendance_date',
        'expected_time',
        'actual_time',
        'late_minutes',
        'deduction_type',
        'deduction_hours',
        'hourly_rate',
        'deduction_amount',
        'policy_version',
        'is_excused',
        'excuse_reason',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_excused' => 'boolean',
        'late_minutes' => 'integer',
        'deduction_hours' => 'float',
        'deduction_amount' => 'float',
    ];

    /**
     * Get human-readable deduction type
     *
     * @return string
     */
    public function getDeductionTypeLabel()
    {
        $labels = [
            'none' => 'No Deduction',
            'grace_period' => 'Grace Period',
            'one_hour' => '1 Hour Deduction',
            'half_day' => 'Half Day Deduction',
            'absent' => 'Absent (Full Day)',
        ];

        return $labels[$this->deduction_type] ?? 'Unknown';
    }

    /**
     * Check if deduction is actually applied (not excused)
     *
     * @return bool
     */
    public function isApplied()
    {
        return !$this->is_excused && $this->deduction_hours > 0;
    }

    /**
     * Get summary of deduction
     *
     * @return array
     */
    public function getSummary()
    {
        return [
            'date' => $this->attendance_date->toDateString(),
            'expected_in' => $this->expected_time,
            'actual_in' => $this->actual_time,
            'late_minutes' => $this->late_minutes,
            'deduction_type' => $this->getDeductionTypeLabel(),
            'deduction_hours' => $this->deduction_hours,
            'deduction_amount' => $this->deduction_amount,
            'is_excused' => $this->is_excused,
            'excuse_reason' => $this->excuse_reason,
        ];
    }

    /**
     * Relationships
     */
    public function timeLog()
    {
        return $this->belongsTo(TimeLog::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
