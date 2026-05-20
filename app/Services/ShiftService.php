<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Employee;
use Carbon\Carbon;

class ShiftService
{
    /**
     * Get an employee's active shift for a specific date
     *
     * @param Employee $employee
     * @param string|Carbon $date
     * @return Shift|null
     */
    public function getEmployeeShiftForDate(Employee $employee, $date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        $assignment = ShiftAssignment::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();

        return $assignment?->shift;
    }

    /**
     * Calculate attendance status based on shift and clock times
     *
     * @param Shift $shift
     * @param string|Carbon $clockInTime
     * @param string|Carbon|null $clockOutTime
     * @param int $gracePeriodMinutes Grace period for lateness
     * @return array
     */
    public function calculateAttendanceStatus(Shift $shift, $clockInTime, $clockOutTime = null, $gracePeriodMinutes = 0)
    {
        $status = 'present';
        $lateMinutes = 0;
        $undertimeMinutes = 0;
        $workingHours = 0;

        if ($clockInTime) {
            $lateMinutes = $shift->checkIfLate($clockInTime, $gracePeriodMinutes);
            if ($lateMinutes > 0) {
                $status = 'late';
            }
        }

        if ($clockOutTime && $clockInTime) {
            $undertimeMinutes = $shift->checkIfUndertime($clockOutTime, $gracePeriodMinutes);
            if ($undertimeMinutes > 0 && $status !== 'late') {
                $status = 'undertime';
            }

            // Calculate actual working hours
            $workingHours = $shift->getWorkingHours($clockInTime, $clockOutTime);
        }

        return [
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'working_hours' => $workingHours,
        ];
    }

    /**
     * Calculate night differential eligibility
     * Employee qualifies if shift is marked as night shift OR crosses midnight
     *
     * @param Shift $shift
     * @param Carbon $shiftDate
     * @return bool
     */
    public function isNightShiftDifferential(Shift $shift, $shiftDate = null)
    {
        return $shift->is_night_shift || $shift->crosses_midnight;
    }

    /**
     * Get all active shifts sorted by order
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActiveShifts()
    {
        return Shift::where('is_active', true)
            ->orderBy('shift_order')
            ->get();
    }

    /**
     * Create or update common shift templates
     *
     * @return array
     */
    public function syncCommonShifts()
    {
        $shiftsData = [
            [
                'name' => 'Morning Shift (8-5)',
                'shift_order' => 1,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_minutes' => 60,
                'is_night_shift' => false,
                'crosses_midnight' => false,
                'shift_duration_minutes' => 480,
            ],
            [
                'name' => '8-4 (No Lunch)',
                'shift_order' => 2,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'break_minutes' => 0,
                'is_night_shift' => false,
                'crosses_midnight' => false,
                'shift_duration_minutes' => 480,
            ],
            [
                'name' => '9-6',
                'shift_order' => 3,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'break_minutes' => 60,
                'is_night_shift' => false,
                'crosses_midnight' => false,
                'shift_duration_minutes' => 480,
            ],
            [
                'name' => '10-7',
                'shift_order' => 4,
                'start_time' => '10:00:00',
                'end_time' => '19:00:00',
                'break_minutes' => 60,
                'is_night_shift' => false,
                'crosses_midnight' => false,
                'shift_duration_minutes' => 480,
            ],
            [
                'name' => '11-8',
                'shift_order' => 5,
                'start_time' => '11:00:00',
                'end_time' => '20:00:00',
                'break_minutes' => 60,
                'is_night_shift' => false,
                'crosses_midnight' => false,
                'shift_duration_minutes' => 480,
            ],
            [
                'name' => 'Graveyard (10pm-7am)',
                'shift_order' => 6,
                'start_time' => '22:00:00',
                'end_time' => '07:00:00',
                'break_minutes' => 60,
                'is_night_shift' => true,
                'crosses_midnight' => true,
                'shift_duration_minutes' => 480,
            ],
            [
                'name' => 'Graveyard (11pm-8am)',
                'shift_order' => 7,
                'start_time' => '23:00:00',
                'end_time' => '08:00:00',
                'break_minutes' => 60,
                'is_night_shift' => true,
                'crosses_midnight' => true,
                'shift_duration_minutes' => 480,
            ],
        ];

        $created = [];
        $updated = [];

        foreach ($shiftsData as $data) {
            $shift = Shift::updateOrCreate(
                ['name' => $data['name']],
                $data
            );

            if ($shift->wasRecentlyCreated) {
                $created[] = $shift;
            } else {
                $updated[] = $shift;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Validate if a time falls within shift hours
     * Accounts for cross-midnight shifts
     *
     * @param Shift $shift
     * @param string|Carbon $time
     * @param string|Carbon $shiftDate
     * @return bool
     */
    public function isTimeWithinShift(Shift $shift, $time, $shiftDate)
    {
        $time = $time instanceof Carbon ? $time : Carbon::parse($time);
        $shiftDate = $shiftDate instanceof Carbon ? $shiftDate : Carbon::parse($shiftDate);

        $shiftStart = $shift->getShiftStartDateTime($shiftDate);
        $shiftEnd = $shift->getShiftEndDateTime($shiftDate);

        return $time->gte($shiftStart) && $time->lte($shiftEnd);
    }

    /**
     * Calculate total payable hours including overtime
     *
     * @param Shift $shift
     * @param float $workingHours
     * @return array
     */
    public function calculatePayableHours(Shift $shift, $workingHours)
    {
        $regularHours = min($workingHours, $shift->shift_duration_minutes / 60);
        $overtimeHours = max(0, $workingHours - $regularHours);

        return [
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'total_hours' => $workingHours,
        ];
    }

    /**
     * Get shift summary for an employee across date range
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getShiftSummary(Employee $employee, Carbon $startDate, Carbon $endDate)
    {
        $summary = [
            'total_days' => 0,
            'total_hours' => 0,
            'shifts_worked' => [],
            'date_range' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
        ];

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $shift = $this->getEmployeeShiftForDate($employee, $current);

            if ($shift) {
                $summary['total_days']++;
                $summary['total_hours'] += $shift->shift_duration_minutes / 60;

                $shiftKey = $shift->name;
                if (!isset($summary['shifts_worked'][$shiftKey])) {
                    $summary['shifts_worked'][$shiftKey] = 0;
                }
                $summary['shifts_worked'][$shiftKey]++;
            }

            $current->addDay();
        }

        return $summary;
    }
}
