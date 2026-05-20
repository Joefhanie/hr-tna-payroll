<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShiftAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Check if this assignment is active on a given date
     *
     * @param string|Carbon $date
     * @return bool
     */
    public function isActiveOn($date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $fromDate = $this->effective_from instanceof Carbon ?
            $this->effective_from : Carbon::parse($this->effective_from);

        $toDate = $this->effective_to ?
            (($this->effective_to instanceof Carbon ? $this->effective_to : Carbon::parse($this->effective_to)))
            : null;

        return $date->gte($fromDate) && (!$toDate || $date->lte($toDate));
    }

    /**
     * Get the shift assigned on a specific date (if this assignment is active)
     *
     * @param string|Carbon $date
     * @return Shift|null
     */
    public function getShiftForDate($date)
    {
        if ($this->isActiveOn($date)) {
            return $this->shift;
        }
        return null;
    }

    /**
     * Check if assignment is currently active (for today)
     *
     * @return bool
     */
    public function isCurrentlyActive()
    {
        return $this->isActiveOn(Carbon::today());
    }

    /**
     * Get the number of days this assignment spans
     *
     * @return int
     */
    public function getDurationInDays()
    {
        $from = $this->effective_from instanceof Carbon ?
            $this->effective_from : Carbon::parse($this->effective_from);

        $to = $this->effective_to instanceof Carbon ?
            $this->effective_to : Carbon::parse($this->effective_to);

        return $from->diffInDays($to) + 1;
    }

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
