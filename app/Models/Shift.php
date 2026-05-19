<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Shift extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'shift_order',
        'start_time',
        'end_time',
        'break_minutes',
        'is_night_shift',
        'crosses_midnight',
        'shift_duration_minutes',
        'days_of_week',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'is_night_shift' => 'boolean',
        'crosses_midnight' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Convert TIME field to minutes since midnight
     * Useful for comparison operations
     *
     * @return int
     */
    public function getStartMinutes()
    {
        [$hours, $minutes] = explode(':', substr($this->start_time, 0, 5));
        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * Convert end TIME field to minutes since midnight
     *
     * @return int
     */
    public function getEndMinutes()
    {
        [$hours, $minutes] = explode(':', substr($this->end_time, 0, 5));
        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * Calculate total shift duration in minutes (including breaks)
     * Handles cross-midnight shifts properly
     *
     * @return int
     */
    public function calculateShiftDuration()
    {
        $startMin = $this->getStartMinutes();
        $endMin = $this->getEndMinutes();

        if ($this->crosses_midnight) {
            // For graveyard (22:00 - 07:00):
            // Minutes from 22:00 to midnight (2 hours) + minutes from midnight to 07:00 (7 hours)
            // = (24*60 - 1320) + 420 = 480 + 420 = 900 minutes = 15 hours
            $duration = (24 * 60 - $startMin) + $endMin;
        } else {
            $duration = $endMin - $startMin;
        }

        return $duration;
    }

    /**
     * Calculate working hours (excluding break time)
     *
     * @return float
     */
    public function getWorkingHoursPerDay()
    {
        $durationMinutes = $this->calculateShiftDuration() - $this->break_minutes;
        return round($durationMinutes / 60, 2);
    }

    /**
     * Check if employee is late for this shift
     * Returns minutes late (0 if on time)
     *
     * @param string|Carbon $clockInTime
     * @param int $gracePeriodMinutes
     * @return int
     */
    public function checkIfLate($clockInTime, $gracePeriodMinutes = 0)
    {
        $clockIn = $clockInTime instanceof Carbon ? $clockInTime : Carbon::parse($clockInTime);

        // Extract time portion as HH:MM
        $clockInMinutes = $clockIn->hour * 60 + $clockIn->minute;
        $shiftStartMinutes = $this->getStartMinutes();

        // Calculate lateness
        $lateMinutes = max(0, $clockInMinutes - $shiftStartMinutes);

        return max(0, $lateMinutes - $gracePeriodMinutes);
    }

    /**
     * Check if employee has undertime (left before shift end)
     * Returns minutes short (0 if completed shift)
     *
     * @param string|Carbon $clockOutTime
     * @param int $gracePeriodMinutes
     * @return int
     */
    public function checkIfUndertime($clockOutTime, $gracePeriodMinutes = 0)
    {
        $clockOut = $clockOutTime instanceof Carbon ? $clockOutTime : Carbon::parse($clockOutTime);

        // Extract time portion as HH:MM
        $clockOutMinutes = $clockOut->hour * 60 + $clockOut->minute;
        $shiftEndMinutes = $this->getEndMinutes();

        // Handle cross-midnight shifts
        // If shift crosses midnight but clock-out is before midnight, add 24 hours worth of minutes
        if ($this->crosses_midnight && $clockOutMinutes < $shiftEndMinutes) {
            // Clock out is in early morning, shift end is also early morning
            // No adjustment needed, just compare directly
            $undertimeMinutes = max(0, $shiftEndMinutes - $clockOutMinutes);
        } elseif ($this->crosses_midnight && $clockOutMinutes >= $shiftEndMinutes) {
            // Clock out is in evening (next day), shift should have ended in morning
            // This means employee clocked out after scheduled end - might be overtime
            $undertimeMinutes = 0;
        } else {
            // Regular shift, both before midnight
            $undertimeMinutes = max(0, $shiftEndMinutes - $clockOutMinutes);
        }

        return max(0, $undertimeMinutes - $gracePeriodMinutes);
    }

    /**
     * Get shift start datetime for a specific date
     *
     * @param string|Carbon $date
     * @return Carbon
     */
    public function getShiftStartDateTime($date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $date->copy()->setTimeFromTimeString($this->start_time);
    }

    /**
     * Get shift end datetime for a specific date
     * Accounts for cross-midnight shifts by adding 1 day if necessary
     *
     * @param string|Carbon $date
     * @return Carbon
     */
    public function getShiftEndDateTime($date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $endDateTime = $date->copy()->setTimeFromTimeString($this->end_time);

        // If shift crosses midnight and end time is before start time (in minutes)
        if ($this->crosses_midnight && $this->getEndMinutes() < $this->getStartMinutes()) {
            $endDateTime->addDay();
        }

        return $endDateTime;
    }

    /**
     * Calculate working hours between two clock times
     * Subtracts break time from total duration
     *
     * @param string|Carbon $clockIn
     * @param string|Carbon $clockOut
     * @return float
     */
    public function getWorkingHours($clockIn, $clockOut)
    {
        $in = $clockIn instanceof Carbon ? $clockIn : Carbon::parse($clockIn);
        $out = $clockOut instanceof Carbon ? $clockOut : Carbon::parse($clockOut);

        $totalMinutes = $out->diffInMinutes($in);
        $workingMinutes = max(0, $totalMinutes - $this->break_minutes);

        return round($workingMinutes / 60, 2);
    }

    /**
     * Check if a specific time falls within this shift's working hours
     *
     * @param string|Carbon $time
     * @param string|Carbon $shiftDate
     * @return bool
     */
    public function containsTime($time, $shiftDate)
    {
        $time = $time instanceof Carbon ? $time : Carbon::parse($time);
        $shiftStart = $this->getShiftStartDateTime($shiftDate);
        $shiftEnd = $this->getShiftEndDateTime($shiftDate);

        return $time->gte($shiftStart) && $time->lte($shiftEnd);
    }

    /**
     * Get display name with hours (e.g., "Morning Shift (8am-5pm, 8h 30m)")
     *
     * @return string
     */
    public function getDisplayNameWithHours()
    {
        $start = Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:ia');
        $end = Carbon::createFromFormat('H:i:s', $this->end_time)->format('g:ia');
        $hours = intdiv($this->shift_duration_minutes ?? 0, 60);
        $minutes = ($this->shift_duration_minutes ?? 0) % 60;

        $duration = $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";

        return "{$this->name} ({$start}-{$end}, {$duration})";
    }

    /**
     * Relationships
     */
    public function shiftAssignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function employees()
    {
        return $this->hasManyThrough(Employee::class, ShiftAssignment::class, 'shift_id', 'id', 'id', 'employee_id');
    }
}
