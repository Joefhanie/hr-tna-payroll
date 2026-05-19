<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'log_date',
        'clock_in',
        'clock_out',
        'source',
        'biometric_device_id',
        'is_remote',
        'ip_address',
        'late_minutes',
        'undertime_minutes',
        'total_hours',
    ];

    protected $casts = [
        'log_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'late_minutes' => 'integer',
        'undertime_minutes' => 'integer',
    ];

    /**
     * Get total break duration for this time log
     * Returns minutes
     *
     * @return int
     */
    public function getTotalBreakMinutes()
    {
        return $this->breakLogs()
            ->whereNotNull('break_end')
            ->get()
            ->sum(function ($log) {
                return $log->getDurationInMinutes();
            });
    }

    /**
     * Get working hours excluding breaks
     * Calculated as: (clock_out - clock_in) - total break time
     *
     * @return float
     */
    public function getNetWorkingHours()
    {
        if (!$this->clock_out) {
            return 0;
        }

        $totalMinutes = $this->clock_in->diffInMinutes($this->clock_out);
        $breakMinutes = $this->getTotalBreakMinutes();
        $workingMinutes = max(0, $totalMinutes - $breakMinutes);

        return round($workingMinutes / 60, 2);
    }

    /**
     * Check if employee has any active/pending break
     *
     * @return bool
     */
    public function hasActiveBreak()
    {
        return $this->breakLogs()
            ->whereNotNull('break_start')
            ->whereNull('break_end')
            ->exists();
    }

    /**
     * Get currently active break (not yet clocked out)
     *
     * @return BreakLog|null
     */
    public function getActiveBreak()
    {
        return $this->breakLogs()
            ->whereNotNull('break_start')
            ->whereNull('break_end')
            ->first();
    }

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function breakLogs()
    {
        return $this->hasMany(BreakLog::class);
    }

    public function lateDeduction()
    {
        return $this->hasOne(LateDeduction::class);
    }

    public function shift()
    {
        return $this->employee()
            ->with('shiftAssignments')
            ->get()
            ->first()
            ?->getActiveShiftForDate($this->log_date);
    }
}
