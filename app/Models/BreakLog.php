<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'time_log_id',
        'break_start',
        'break_end',
        'break_type',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    /**
     * Get break duration in minutes
     *
     * @return int
     */
    public function getDurationInMinutes()
    {
        if (!$this->break_end) {
            return 0;
        }

        return $this->break_start->diffInMinutes($this->break_end);
    }

    /**
     * Get break duration as formatted string (e.g., "1h 30m")
     *
     * @return string
     */
    public function getFormattedDuration()
    {
        $minutes = $this->getDurationInMinutes();
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours === 0) {
            return "{$mins}m";
        }
        if ($mins === 0) {
            return "{$hours}h";
        }
        return "{$hours}h {$mins}m";
    }

    /**
     * Check if break is still active (not yet clocked out)
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->break_start !== null && $this->break_end === null;
    }

    /**
     * Check if break has been completed
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->break_start !== null && $this->break_end !== null;
    }

    /**
     * Get break status string
     *
     * @return string
     */
    public function getStatus()
    {
        if ($this->isActive()) {
            return 'active';
        }
        if ($this->isCompleted()) {
            return 'completed';
        }
        return 'pending';
    }

    /**
     * Relationships
     */
    public function timeLog()
    {
        return $this->belongsTo(TimeLog::class);
    }
}
