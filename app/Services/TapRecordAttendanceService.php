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
    protected ?bool $useMachine = null;

    protected ?array $functionSettingIdsByValue = null;

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

        $mappedPunches = $this->resolvePunchesFromFunctionSettings($tapRecords);

        if ($mappedPunches !== null) {
            $timeInTap = $mappedPunches['time_in'];
            $timeOutTap = $mappedPunches['time_out'];
        } else {
            $timeInTap = $tapRecords->first();
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
        }

        $timeIn = Carbon::parse($timeInTap->time);

        $timeOut = $timeOutTap ? Carbon::parse($timeOutTap->time) : null;

        $status = ($mappedPunches !== null && ($mappedPunches['is_on_break'] ?? false))
            ? 6
            : $this->resolveStatus($shift, $timeIn, $timeOut);

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

    private function resolvePunchesFromFunctionSettings(Collection $tapRecords): ?array
    {
        if (!$this->isMachineEnabled()) {
            return null;
        }

        $functionColumn = $this->resolveTapFunctionColumn($tapRecords);

        if ($functionColumn === null) {
            return null;
        }

        $functionSettingIdsByValue = $this->getFunctionSettingIdsByValue();

        if (!isset($functionSettingIdsByValue[1], $functionSettingIdsByValue[2])) {
            return null;
        }

        $timeInCandidates = $this->resolveFunctionCandidates($functionSettingIdsByValue, 1);
        $timeOutCandidates = $this->resolveFunctionCandidates($functionSettingIdsByValue, 2);
        $breakInCandidates = isset($functionSettingIdsByValue[3])
            ? $this->resolveFunctionCandidates($functionSettingIdsByValue, 3)
            : [];
        $breakOutCandidates = isset($functionSettingIdsByValue[4])
            ? $this->resolveFunctionCandidates($functionSettingIdsByValue, 4)
            : [];

        $timeInTap = $tapRecords->first(function ($tap) use ($functionColumn, $timeInCandidates) {
            return in_array((int) data_get($tap, $functionColumn), $timeInCandidates, true);
        });

        if (!$timeInTap) {
            return null;
        }

        $timeInMoment = Carbon::parse($timeInTap->time);
        $timeOutTap = $tapRecords
            ->filter(function ($tap) use ($functionColumn, $timeOutCandidates, $timeInMoment) {
                if (!in_array((int) data_get($tap, $functionColumn), $timeOutCandidates, true)) {
                    return false;
                }

                return Carbon::parse($tap->time)->gte($timeInMoment);
            })
            ->last();

        return [
            'time_in' => $timeInTap,
            'time_out' => $timeOutTap,
            'break_in' => $this->resolveTapBySettingValue($tapRecords, $functionColumn, 3),
            'break_out' => $this->resolveTapBySettingValue($tapRecords, $functionColumn, 4),
            'is_on_break' => $this->isOnBreakState($tapRecords, $functionColumn, $breakInCandidates, $breakOutCandidates),
        ];
    }

    private function isOnBreakState(Collection $tapRecords, string $functionColumn, array $breakInCandidates, array $breakOutCandidates): bool
    {
        if ($breakInCandidates === [] || $breakOutCandidates === []) {
            return false;
        }

        $lastBreakTap = $tapRecords
            ->filter(function ($tap) use ($functionColumn, $breakInCandidates, $breakOutCandidates) {
                $tapFunction = (int) data_get($tap, $functionColumn);

                return in_array($tapFunction, $breakInCandidates, true)
                    || in_array($tapFunction, $breakOutCandidates, true);
            })
            ->sortBy(function ($tap) {
                return Carbon::parse($tap->time)->timestamp;
            })
            ->last();

        if (!$lastBreakTap) {
            return false;
        }

        return in_array((int) data_get($lastBreakTap, $functionColumn), $breakInCandidates, true);
    }

    private function resolveTapBySettingValue(Collection $tapRecords, string $functionColumn, int $settingValue): mixed
    {
        $functionSettingIdsByValue = $this->getFunctionSettingIdsByValue();

        if (!isset($functionSettingIdsByValue[$settingValue])) {
            return null;
        }

        $candidates = $this->resolveFunctionCandidates($functionSettingIdsByValue, $settingValue);

        return $tapRecords->first(function ($tap) use ($functionColumn, $candidates) {
            return in_array((int) data_get($tap, $functionColumn), $candidates, true);
        });
    }

    private function resolveFunctionCandidates(array $functionSettingIdsByValue, int $settingValue): array
    {
        $candidates = [$settingValue];
        $mappedId = $functionSettingIdsByValue[$settingValue] ?? null;

        if ($mappedId !== null) {
            $candidates[] = (int) $mappedId;
        }

        return array_values(array_unique($candidates));
    }

    private function resolveTapFunctionColumn(Collection $tapRecords): ?string
    {
        $firstTap = $tapRecords->first();

        if (!$firstTap) {
            return null;
        }

        $tapColumns = array_keys(get_object_vars($firstTap));
        $candidateColumns = [
            'function',
            'function_id',
            'function_setting_id',
            'setting_id',
            'tap_function',
        ];

        foreach ($candidateColumns as $candidateColumn) {
            if (in_array($candidateColumn, $tapColumns, true)) {
                return $candidateColumn;
            }
        }

        return null;
    }

    private function getFunctionSettingIdsByValue(): array
    {
        if ($this->functionSettingIdsByValue !== null) {
            return $this->functionSettingIdsByValue;
        }

        $this->functionSettingIdsByValue = DB::table('function_settings')
            ->whereNotNull('setting_value')
            ->orderBy('id')
            ->get(['id', 'setting_value'])
            ->mapWithKeys(function ($row) {
                return [(int) $row->setting_value => (int) $row->id];
            })
            ->all();

        return $this->functionSettingIdsByValue;
    }

    private function isMachineEnabled(): bool
    {
        if ($this->useMachine !== null) {
            return $this->useMachine;
        }

        $this->useMachine = (bool) CompanySetting::current()->use_machine;

        return $this->useMachine;
    }
}
