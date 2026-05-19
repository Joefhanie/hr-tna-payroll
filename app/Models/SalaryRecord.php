<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    protected $table = 'salary_records';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'amount',
        'pay_frequency',
        'daily_divisor',
        'attendance_overtime_multiplier',
        'attendance_night_differential_multiplier',
        'attendance_late_deduction_multiplier',
        'attendance_undertime_deduction_multiplier',
        'attendance_absence_deduction_multiplier',
        'effective_date',
        'end_date',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'daily_divisor' => 'decimal:4',
        'attendance_overtime_multiplier' => 'decimal:4',
        'attendance_night_differential_multiplier' => 'decimal:4',
        'attendance_late_deduction_multiplier' => 'decimal:4',
        'attendance_undertime_deduction_multiplier' => 'decimal:4',
        'attendance_absence_deduction_multiplier' => 'decimal:4',
        'effective_date' => 'date',
        'end_date' => 'date',
        'pay_frequency' => 'integer',
    ];

    /**
     * Get the employee associated with this salary record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created this record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
