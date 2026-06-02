<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Masterlist;
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
            // Resolve taps by masterlist_id only
            $tapRecords = DB::table('tap_records')
                ->where('masterlist_id', $employee->masterlist_id)
                ->whereBetween('time', [$date->copy()->startOfDay()->toDateTimeString(), $windowEnd->toDateTimeString()])
                ->orderBy('time')
                ->get();
        }

        if ($tapRecords->isEmpty()) {
            return null;
        }

        $useFunctionCodes = $this->hasCompleteFunctionSettings();

        // When the function settings table is incomplete or contains nulls,
        // treat the tap list as plain punches and use the first/last record.
        if (! $useFunctionCodes) {
            // Legacy untyped taps: prefer first tap as In. For Out, only set when
            // there's a clear second tap or when the tap is at/after shift end
            // (or the shift has already passed). This avoids creating an Out
            // equal to In when there's only a single early tap.
            if ($tapRecords->count() === 1) {
                $singleTap = $tapRecords->first();
                $timeInTap = $singleTap;
                $timeOutTap = null;

                if ($shift) {
                    $shiftEnd = $shift->getShiftEndDateTime($date);
                    // If the single tap is at/after shift end, or the current
                    // time is already past shift end, allow it as an Out.
                    if (Carbon::parse($singleTap->time)->gte($shiftEnd) || $now->gte($shiftEnd)) {
                        $timeOutTap = $singleTap;
                    }
                } else {
                    // No shift info: treat single tap as both In and Out.
                    $timeOutTap = $singleTap;
                }
            } else {
                $timeInTap = $tapRecords->first();
                $timeOutTap = $tapRecords->last();
            }
        } else {
            // Function code mapping (from function_settings table):
            //   1 = Check In  |  2 = Check Out  |  3 = Break In  |  4 = Break Out
            $checkInTaps = $tapRecords->filter(fn($t) => isset($t->function) && (int) $t->function === 1);
            $checkOutTaps = $tapRecords->filter(fn($t) => isset($t->function) && (int) $t->function === 2);
            $hasFunctionCodes = $tapRecords->contains(fn($t) => isset($t->function) && in_array((int) $t->function, [1, 2, 3, 4], true));

            // Use function-specific taps when available; fall back to first/last for
            // records without a function code (legacy / untyped imports).
            $timeInTap = $checkInTaps->isNotEmpty()
                ? $checkInTaps->first()
                : ($hasFunctionCodes ? null : $tapRecords->first());

            $timeOutTap = null;

            if ($checkOutTaps->isNotEmpty()) {
                // Prefer the last Check Out tap after shift end; otherwise the last Check Out of the day.
                if ($shift && $now->gte($shift->getShiftEndDateTime($date))) {
                    $shiftEnd = $shift->getShiftEndDateTime($date);
                    $checkOutsAfterShift = $checkOutTaps->filter(
                        fn($t) => Carbon::parse($t->time)->gte($shiftEnd)
                    );
                    $timeOutTap = $checkOutsAfterShift->isNotEmpty()
                        ? $checkOutsAfterShift->last()
                        : $checkOutTaps->last();
                } else {
                    $timeOutTap = $checkOutTaps->last();
                }
            } elseif (!$hasFunctionCodes && $shift && $now->gte($shift->getShiftEndDateTime($date))) {
                // Legacy fallback: no function codes at all â€” use last tap after shift end.
                $shiftEnd = $shift->getShiftEndDateTime($date);
                $tapsAfterShift = $tapRecords->filter(
                    fn($t) => Carbon::parse($t->time)->gte($shiftEnd)
                );
                $timeOutTap = $tapsAfterShift->isNotEmpty()
                    ? $tapsAfterShift->last()
                    : $tapRecords->last();
            }
        }

        $existingInPunch = Attendance::query()
            ->where('emp_id', $employee->id)
            ->where('attendance_date', $dateString)
            ->where('punch_type', 'in')
            ->first();

        $manualTimeIn = null;
        if ($existingInPunch && $existingInPunch->time) {
            $existingTimeStr = Carbon::parse($existingInPunch->time)->format('H:i:s');
            $isFromTap = $tapRecords->contains(function ($t) use ($existingTimeStr) {
                return $t->time && Carbon::parse($t->time)->format('H:i:s') === $existingTimeStr;
            });
            if (!$isFromTap) {
                $manualTimeIn = Carbon::parse($existingInPunch->time);
            }
        }

        if (!$timeInTap && !$existingInPunch) {
            return null;
        }

        $timeIn = $manualTimeIn ?? ($timeInTap ? Carbon::parse($timeInTap->time) : Carbon::parse($existingInPunch->time));

        $existingOutPunch = Attendance::query()
            ->where('emp_id', $employee->id)
            ->where('attendance_date', $dateString)
            ->where('punch_type', 'out')
            ->first();

        $manualTimeOut = null;
        if ($existingOutPunch && $existingOutPunch->time) {
            $existingTimeStr = Carbon::parse($existingOutPunch->time)->format('H:i:s');
            $isFromTap = $tapRecords->contains(function ($t) use ($existingTimeStr) {
                return $t->time && Carbon::parse($t->time)->format('H:i:s') === $existingTimeStr;
            });
            if (!$isFromTap) {
                $manualTimeOut = Carbon::parse($existingOutPunch->time);
            }
        }

        $timeOut = $manualTimeOut ?? ($timeOutTap ? Carbon::parse($timeOutTap->time) : null);

        $lastBreakTap = $tapRecords
            ->filter(fn($t) => isset($t->function) && in_array((int) $t->function, [3, 4], true))
            ->sortBy(fn($t) => Carbon::parse($t->time)->timestamp)
            ->last();
        $isOnBreak = $lastBreakTap && (int) $lastBreakTap->function === 3;

        $status = $isOnBreak ? 6 : $this->resolveStatus($shift, $timeIn, $timeOut);

        $attendance = Attendance::upsertPunch(
            $employee->id,
            $dateString,
            'in',
            $timeIn,
            $shift?->id,
            $status
        );

        if ($timeOut) {
            // Avoid creating an Out punch identical to the In punch — this
            // commonly happens when a single tap was treated as both In and Out.
            $shouldCreateOut = true;
            if ($timeIn && $timeOut) {
                try {
                    $shouldCreateOut = ! (Carbon::parse($timeIn)->format('H:i:s') === Carbon::parse($timeOut)->format('H:i:s'));
                } catch (\Throwable $e) {
                    // If parsing fails, fall back to creating the Out.
                    $shouldCreateOut = true;
                }
            }

            if ($shouldCreateOut) {
                Attendance::upsertPunch(
                    $employee->id,
                    $dateString,
                    'out',
                    $timeOut,
                    $shift?->id,
                    $status
                );
            }
        }

        return $attendance;
    }

    public function syncForTapRecord(object $tapRecord): ?Attendance
    {
        $employee = $this->resolveEmployeeFromTapRecord($tapRecord);

        if (!$employee) {
            return null;
        }

        return $this->syncForEmployeeDate($employee, Carbon::parse($tapRecord->time));
    }

    public function syncAll(): int
    {
        // Only consider taps that have a masterlist_id; ignore employee_id values
        $tapRecords = DB::table('tap_records')
            ->select('masterlist_id', 'time', 'function')
            ->whereNotNull('masterlist_id')
            ->orderBy('time')
            ->get();

        if ($tapRecords->isEmpty()) {
            return 0;
        }

        $employeeRefs = $tapRecords
            ->pluck('masterlist_id')
            ->filter()
            ->unique()
            ->values();

        $employeesByMasterlist = Employee::with(['shiftAssignments.shift'])
            ->whereIn('masterlist_id', $employeeRefs)
            ->get()
            ->keyBy('masterlist_id');

        $recordsByEmployeeDate = $tapRecords->groupBy(function ($tapRecord) {
            // Group by masterlist_id and tap date only
            return $tapRecord->masterlist_id . '|' . Carbon::parse($tapRecord->time)->toDateString();
        });

        $synced = 0;

        foreach ($recordsByEmployeeDate as $groupKey => $records) {
            [$employeeRef, $tapDate] = explode('|', $groupKey, 2);
            $employee = $employeesByMasterlist->get((int) $employeeRef);

            if (!$employee) {
                continue;
            }

            if ($this->syncForEmployeeDate($employee, Carbon::parse($tapDate), $records)) {
                $synced++;
            }
        }

        return $synced;
    }

    private function resolveEmployeeFromTapRecord(object $tapRecord): ?Employee
    {
        $masterlistId = $tapRecord->masterlist_id ?? null;

        if ($masterlistId) {
            $masterlist = Masterlist::with(['employee.shiftAssignments.shift'])
                ->find((int) $masterlistId);

            if ($masterlist?->employee) {
                return $masterlist->employee;
            }

            $masterlistEmployeeId = $masterlist?->emp_id;
            if ($masterlistEmployeeId) {
                return Employee::with(['shiftAssignments.shift'])->find($masterlistEmployeeId);
            }
        }

        return null;
    }

    protected function resolveStatus(?\App\Models\Shift $shift, ?Carbon $timeIn, ?Carbon $timeOut): int
    {
        if (!$shift || !$timeIn) {
            return 1;
        }

        $attendanceStatus = $this->shiftService->calculateAttendanceStatus($shift, $timeIn, $timeOut);

        return $attendanceStatus['status'] === 'late' ? 2 : 1;
    }

    private function hasCompleteFunctionSettings(): bool
    {
        $values = DB::table('function_settings')
            ->whereIn('setting_value', [1, 2, 3, 4])
            ->pluck('setting_value')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $hasNullSetting = DB::table('function_settings')
            ->whereNull('setting_value')
            ->exists();

        return ! $hasNullSetting && $values->count() === 4 && $values->sort()->values()->all() === [1, 2, 3, 4];
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
