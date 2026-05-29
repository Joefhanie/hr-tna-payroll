<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TapRecordAttendanceService
{
    public function __construct(protected ShiftService $shiftService)
    {
    }

    public function syncForEmployeeDate(Employee $employee, $date, ?Collection $tapRecords = null): ?Attendance
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $dateString = $date->toDateString();
        $now = now('Asia/Manila');

        $shift = $employee->relationLoaded('shiftAssignments')
            ? $this->resolveActiveShiftFromLoadedAssignments($employee, $date)
            : $this->shiftService->getEmployeeShiftForDate($employee, $date);
        $windowEnd = $date->copy()->endOfDay();

        if ($shift && $shift->crosses_midnight) {
            $windowEnd = $date->copy()->addDay()->endOfDay();
        }

        if ($tapRecords === null) {
            $tapRecords = DB::table('tap_records')
                ->where(function ($query) use ($employee) {
                    $query->where('employee_id', $employee->id)
                        ->orWhere('masterlist_id', $employee->id);
                })
                ->whereBetween('time', [$date->copy()->startOfDay()->toDateTimeString(), $windowEnd->toDateTimeString()])
                ->orderBy('time')
                ->get();
        }

        if ($tapRecords->isEmpty()) {
            return null;
        }

        $timeInTap = $tapRecords->first();
        $timeIn = Carbon::parse($timeInTap->time);

        $timeOutTap = null;

        if ($shift && $now->gte($shift->getShiftEndDateTime($date))) {
            $shiftEnd = $shift->getShiftEndDateTime($date);
            $tapsAfterShift = $tapRecords->filter(function ($tap) use ($shiftEnd) {
                return Carbon::parse($tap->time)->gte($shiftEnd);
            });

            $timeOutTap = $tapsAfterShift->isNotEmpty()
                ? $tapsAfterShift->last()
                : $tapRecords->last();
        }

        $timeOut = $timeOutTap ? Carbon::parse($timeOutTap->time) : null;

        $status = $this->resolveStatus($shift, $timeIn, $timeOut);

        $attendance = Attendance::upsertPunch(
            $employee->id,
            $dateString,
            'in',
            $timeIn,
            $shift?->id,
            $status
        );

        if ($timeOut) {
            Attendance::upsertPunch(
                $employee->id,
                $dateString,
                'out',
                $timeOut,
                $shift?->id,
                $status
            );
        }

        return $attendance;
    }

    public function syncForTapRecord(object $tapRecord): ?Attendance
    {
        $employeeId = $tapRecord->employee_id ?? $tapRecord->masterlist_id ?? null;

        if (!$employeeId) {
            return null;
        }

        $employee = Employee::with(['user', 'shiftAssignments.shift'])->find($employeeId);

        if (!$employee || !$employee->user) {
            return null;
        }

        return $this->syncForEmployeeDate($employee, Carbon::parse($tapRecord->time));
    }

    public function syncAll(): int
    {
        $tapRecords = DB::table('tap_records')
            ->select('employee_id', 'masterlist_id', 'time')
            ->where(function ($query) {
                $query->whereNotNull('employee_id')
                    ->orWhereNotNull('masterlist_id');
            })
            ->orderBy('time')
            ->get();

        if ($tapRecords->isEmpty()) {
            return 0;
        }

        $employeeRefs = $tapRecords
            ->map(function ($tapRecord) {
                return $tapRecord->employee_id ?? $tapRecord->masterlist_id;
            })
            ->filter()
            ->unique()
            ->values();

        $employees = Employee::with(['user', 'shiftAssignments.shift'])
            ->whereIn('id', $employeeRefs)
            ->get()
            ->keyBy('id');

        $recordsByEmployeeDate = $tapRecords->groupBy(function ($tapRecord) {
            $employeeRef = $tapRecord->employee_id ?? $tapRecord->masterlist_id;

            return $employeeRef . '|' . Carbon::parse($tapRecord->time)->toDateString();
        });

        $synced = 0;

        foreach ($recordsByEmployeeDate as $groupKey => $records) {
            [$employeeRef, $tapDate] = explode('|', $groupKey, 2);
            $employee = $employees->get((int) $employeeRef);

            if (!$employee || !$employee->user) {
                continue;
            }

            if ($this->syncForEmployeeDate($employee, Carbon::parse($tapDate), $records)) {
                $synced++;
            }
        }

        return $synced;
    }

    protected function resolveStatus(?\App\Models\Shift $shift, ?Carbon $timeIn, ?Carbon $timeOut): int
    {
        if (!$shift || !$timeIn) {
            return 1;
        }

        $attendanceStatus = $this->shiftService->calculateAttendanceStatus($shift, $timeIn, $timeOut);

        return $attendanceStatus['status'] === 'late' ? 2 : 1;
    }

    private function resolveActiveShiftFromLoadedAssignments(Employee $employee, Carbon $date): ?\App\Models\Shift
    {
        $dayOfWeek = $date->format('D');
        $dateString = $date->toDateString();

        $assignments = $employee->shiftAssignments
            ->filter(function ($assignment) use ($dateString) {
                $effectiveFrom = $assignment->effective_from instanceof Carbon
                    ? $assignment->effective_from->toDateString()
                    : Carbon::parse($assignment->effective_from)->toDateString();

                $effectiveTo = $assignment->effective_to
                    ? (($assignment->effective_to instanceof Carbon)
                        ? $assignment->effective_to->toDateString()
                        : Carbon::parse($assignment->effective_to)->toDateString())
                    : null;

                return $effectiveFrom <= $dateString && (!$effectiveTo || $effectiveTo >= $dateString);
            })
            ->sortByDesc(function ($assignment) {
                return $assignment->effective_from instanceof Carbon
                    ? $assignment->effective_from->timestamp
                    : Carbon::parse($assignment->effective_from)->timestamp;
            })
            ->values();

        if (\Illuminate\Support\Facades\Schema::hasColumn('shift_assignments', 'status')) {
            $assignments = $assignments->filter(function ($assignment) {
                return $assignment->status === null || (int) $assignment->status !== 13;
            })->values();
        }

        foreach ($assignments as $assignment) {
            if ($assignment->shift && is_array($assignment->shift->days_of_week) && in_array($dayOfWeek, $assignment->shift->days_of_week, true)) {
                return $assignment->shift;
            }
        }

        return $assignments->first()?->shift;
    }
}
