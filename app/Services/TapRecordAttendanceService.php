<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompanySetting;
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
                    $query->where('masterlist_id', $employee->masterlist_id)
                        ->orWhere('employee_id', $employee->id);
                })
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
            $timeInTap = $tapRecords->first();
            $timeOutTap = $tapRecords->last();
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
                // Legacy fallback: no function codes at all — use last tap after shift end.
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
        $employee = $this->resolveEmployeeFromTapRecord($tapRecord);

        if (!$employee || !$employee->user) {
            return null;
        }

        return $this->syncForEmployeeDate($employee, Carbon::parse($tapRecord->time));
    }

    public function syncAll(): int
    {
        $tapRecords = DB::table('tap_records')
            ->select('employee_id', 'masterlist_id', 'time', 'function')
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
            ->flatMap(function ($tapRecord) {
                return [$tapRecord->masterlist_id, $tapRecord->employee_id];
            })
            ->filter()
            ->unique()
            ->values();

        $employeesById = Employee::with(['user', 'shiftAssignments.shift'])
            ->whereIn('id', $employeeRefs)
            ->get()
            ->keyBy('id');

        $employeesByMasterlist = Employee::with(['user', 'shiftAssignments.shift'])
            ->whereIn('masterlist_id', $employeeRefs)
            ->get()
            ->keyBy('masterlist_id');

        $recordsByEmployeeDate = $tapRecords->groupBy(function ($tapRecord) use ($employeesByMasterlist, $employeesById) {
            $employee = $this->resolveEmployeeFromTapRecord($tapRecord, $employeesByMasterlist, $employeesById);
            $employeeRef = $employee?->masterlist_id ?? $employee?->id ?? ($tapRecord->masterlist_id ?? $tapRecord->employee_id);

            return $employeeRef . '|' . Carbon::parse($tapRecord->time)->toDateString();
        });

        $synced = 0;

        foreach ($recordsByEmployeeDate as $groupKey => $records) {
            [$employeeRef, $tapDate] = explode('|', $groupKey, 2);
            $employee = $employeesByMasterlist->get((int) $employeeRef)
                ?? $employeesById->get((int) $employeeRef);

            if (!$employee || !$employee->user) {
                continue;
            }

            if ($this->syncForEmployeeDate($employee, Carbon::parse($tapDate), $records)) {
                $synced++;
            }
        }

        return $synced;
    }

    private function resolveEmployeeFromTapRecord(object $tapRecord, ?Collection $employeesByMasterlist = null, ?Collection $employeesById = null): ?Employee
    {
        $masterlistId = $tapRecord->masterlist_id ?? null;
        $employeeId = $tapRecord->employee_id ?? null;

        if ($masterlistId) {
            $masterlist = Masterlist::with(['employee.user', 'employee.shiftAssignments.shift'])
                ->find((int) $masterlistId);

            if ($masterlist?->employee) {
                return $masterlist->employee;
            }

            $masterlistEmployeeId = $masterlist?->emp_id;
            if ($employeesById && $masterlistEmployeeId && $employeesById->has((int) $masterlistEmployeeId)) {
                return $employeesById->get((int) $masterlistEmployeeId);
            }
        }

        if ($employeesById && $employeeId && $employeesById->has((int) $employeeId)) {
            return $employeesById->get((int) $employeeId);
        }

        if ($masterlistId) {
            $fallbackMasterlist = Masterlist::query()->find((int) $masterlistId);
            if ($fallbackMasterlist?->emp_id) {
                return Employee::with(['user', 'shiftAssignments.shift'])->find($fallbackMasterlist->emp_id);
            }
        }

        return Employee::with(['user', 'shiftAssignments.shift'])->find($employeeId);
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
