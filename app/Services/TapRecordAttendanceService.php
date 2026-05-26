<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TapRecordAttendanceService
{
    public function __construct(protected ShiftService $shiftService)
    {
    }

    public function syncForEmployeeDate(Employee $employee, $date): ?Attendance
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $dateString = $date->toDateString();
        $now = now('Asia/Manila');

        $shift = $this->shiftService->getEmployeeShiftForDate($employee, $date);
        $windowEnd = $date->copy()->endOfDay();

        if ($shift && $shift->crosses_midnight) {
            $windowEnd = $date->copy()->addDay()->endOfDay();
        }

        $tapRecords = DB::table('tap_records')
            ->where(function ($query) use ($employee) {
                $query->where('employee_id', $employee->id)
                    ->orWhere('masterlist_id', $employee->id);
            })
            ->whereBetween('time', [$date->copy()->startOfDay()->toDateTimeString(), $windowEnd->toDateTimeString()])
            ->orderBy('time')
            ->get();

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

        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $employee->user?->id,
                'attendance_date' => $dateString,
            ],
            [
                'shift_id' => $shift?->id,
                'check_in' => $timeIn,
                'check_out' => $timeOut,
                'status' => $this->resolveStatus($shift, $timeIn, $timeOut),
                'notes' => 'Synced from tap records.',
            ]
        );

        return $attendance;
    }

    public function syncForTapRecord(object $tapRecord): ?Attendance
    {
        $employeeId = $tapRecord->employee_id ?? $tapRecord->masterlist_id ?? null;

        if (!$employeeId) {
            return null;
        }

        $employee = Employee::with('user')->find($employeeId);

        if (!$employee || !$employee->user) {
            return null;
        }

        return $this->syncForEmployeeDate($employee, Carbon::parse($tapRecord->time));
    }

    public function syncAll(): int
    {
        $pairs = DB::table('tap_records')
            ->selectRaw('COALESCE(employee_id, masterlist_id) as employee_ref, DATE(time) as tap_date')
            ->whereRaw('COALESCE(employee_id, masterlist_id) IS NOT NULL')
            ->groupBy('employee_ref', 'tap_date')
            ->orderBy('tap_date')
            ->get();

        $synced = 0;

        foreach ($pairs as $pair) {
            $employee = Employee::with('user')->find($pair->employee_ref);

            if (!$employee || !$employee->user) {
                continue;
            }

            if ($this->syncForEmployeeDate($employee, $pair->tap_date)) {
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
}
