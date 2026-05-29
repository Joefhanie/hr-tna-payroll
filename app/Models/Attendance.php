<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'emp_id',
        'shift_id',
        'punch_type',
        'attendance_date',
        'time',
        'status',
    ];

    protected $appends = [
        'entry_type',
        'check_in',
        'check_out',
        'check_in_time',
        'check_out_time',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'time' => 'datetime:H:i',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emp_id', 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function getEntryTypeAttribute(): ?string
    {
        return $this->punch_type;
    }

    public function getCheckInAttribute($value): ?Carbon
    {
        if ($this->isSummaryOnlyStatus()) {
            return null;
        }

        if ($this->punch_type === 'in') {
            return $this->formatTimeValue($this->getRawOriginal('time') ?? $value);
        }

        return $this->pairedPunchTime('in');
    }

    public function getCheckOutAttribute($value): ?Carbon
    {
        if ($this->isSummaryOnlyStatus()) {
            return null;
        }

        if ($this->punch_type === 'out') {
            return $this->formatTimeValue($this->getRawOriginal('time') ?? $value);
        }

        return $this->pairedPunchTime('out');
    }

    public function getNotesAttribute($value): ?string
    {
        return $value;
    }

    public function getCheckInTimeAttribute(): ?string
    {
        if ($this->punch_type === 'in') {
            return $this->normalizeTimeString($this->getRawOriginal('time'));
        }

        return $this->normalizeTimeString($this->pairedPunchRawTime('in'));
    }

    public function getCheckOutTimeAttribute(): ?string
    {
        if ($this->punch_type === 'out') {
            return $this->normalizeTimeString($this->getRawOriginal('time'));
        }

        return $this->normalizeTimeString($this->pairedPunchRawTime('out'));
    }

    public function setUserIdAttribute($value): void
    {
        $employeeId = User::query()->whereKey($value)->value('employee_id');

        $this->attributes['emp_id'] = $employeeId ?? $value;
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('emp_id', $employeeId);
    }

    public function scopePunchIn($query)
    {
        return $query->where('punch_type', 'in');
    }

    public function scopePunchOut($query)
    {
        return $query->where('punch_type', 'out');
    }

    public static function upsertPunch(int $employeeId, string|Carbon $attendanceDate, string $punchType, string|Carbon|null $time, ?int $shiftId = null, int $status = 1): self
    {
        $attendanceDate = $attendanceDate instanceof Carbon ? $attendanceDate->toDateString() : Carbon::parse($attendanceDate)->toDateString();
        $timeValue = $time instanceof Carbon ? $time->format('H:i:s') : (is_string($time) ? Carbon::parse($time)->format('H:i:s') : null);

        $attendance = static::query()->updateOrCreate(
            [
                'emp_id' => $employeeId,
                'attendance_date' => $attendanceDate,
                'punch_type' => $punchType,
            ],
            [
                'shift_id' => $shiftId,
                'time' => $timeValue,
                'status' => $status,
            ]
        );

        return $attendance;
    }

    public function pairedPunchTime(string $punchType): ?Carbon
    {
        if (! $this->emp_id || ! $this->attendance_date) {
            return null;
        }

        $timeValue = static::query()
            ->where('emp_id', $this->emp_id)
            ->whereDate('attendance_date', $this->attendance_date instanceof Carbon ? $this->attendance_date->toDateString() : $this->attendance_date)
            ->where('punch_type', $punchType)
            ->value('time');

        return $this->formatTimeValue($timeValue);
    }

    protected function pairedPunchRawTime(string $punchType): ?string
    {
        if (! $this->emp_id || ! $this->attendance_date) {
            return null;
        }

        return static::query()
            ->where('emp_id', $this->emp_id)
            ->whereDate('attendance_date', $this->attendance_date instanceof Carbon ? $this->attendance_date->toDateString() : $this->attendance_date)
            ->where('punch_type', $punchType)
            ->value('time');
    }

    protected function isSummaryOnlyStatus(): bool
    {
        return in_array((int) ($this->status ?? 0), [3, 4, 5], true);
    }

    protected function formatTimeValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i:s', (string) $value);
        } catch (\Throwable $e) {
            return Carbon::parse((string) $value);
        }
    }

    protected function normalizeTimeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i:s', (string) $value)->format('H:i');
        } catch (\Throwable $e) {
            return Carbon::parse((string) $value)->format('H:i');
        }
    }
}
