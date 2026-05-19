<?php

namespace App\Services;

use App\Models\LateDeduction;
use App\Models\TimeLog;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\SalaryRecord;
use Carbon\Carbon;

class LateDeductionService
{
    /**
     * Late policy configuration
     * Can be overridden per company/department
     */
    protected $policy = [
        'grace_period_minutes' => 10,
        'thresholds' => [
            ['min' => 0, 'max' => 10, 'type' => 'grace_period', 'deduction_hours' => 0],
            ['min' => 11, 'max' => 30, 'type' => 'one_hour', 'deduction_hours' => 1],
            ['min' => 31, 'max' => 60, 'type' => 'half_day', 'deduction_hours' => 4],
            ['min' => 61, 'max' => 99999, 'type' => 'absent', 'deduction_hours' => 8],
        ],
    ];

    /**
     * Calculate late deduction for employee
     *
     * @param Employee $employee
     * @param Shift $shift
     * @param Carbon|string $actualClockIn
     * @param Carbon|string $attendanceDate
     * @return array
     */
    public function calculateLateDeduction(Employee $employee, Shift $shift, $actualClockIn, $attendanceDate)
    {
        $attendanceDate = $attendanceDate instanceof Carbon ? $attendanceDate : Carbon::parse($attendanceDate);
        $actualClockIn = $actualClockIn instanceof Carbon ? $actualClockIn : Carbon::parse($actualClockIn);

        // Get expected time (shift start)
        $expectedClockIn = $shift->getShiftStartDateTime($attendanceDate);

        // Calculate minutes late
        $lateMinutes = max(0, $actualClockIn->diffInMinutes($expectedClockIn));

        // Determine deduction type and hours
        $deduction = $this->getDeductionForLateMinutes($lateMinutes);

        // Calculate monetary deduction
        $hourlyRate = $this->getEmployeeHourlyRate($employee, $attendanceDate);
        $deductionAmount = $deduction['deduction_hours'] > 0
            ? round($deduction['deduction_hours'] * $hourlyRate, 2)
            : 0;

        return [
            'expected_time' => $expectedClockIn->format('H:i:s'),
            'actual_time' => $actualClockIn->format('H:i:s'),
            'late_minutes' => $lateMinutes,
            'deduction_type' => $deduction['type'],
            'deduction_hours' => $deduction['deduction_hours'],
            'hourly_rate' => $hourlyRate,
            'deduction_amount' => $deductionAmount,
        ];
    }

    /**
     * Determine deduction based on late minutes
     *
     * @param int $lateMinutes
     * @param string|null $policyVersion
     * @return array
     */
    public function getDeductionForLateMinutes($lateMinutes, $policyVersion = null)
    {
        foreach ($this->policy['thresholds'] as $threshold) {
            if ($lateMinutes >= $threshold['min'] && $lateMinutes <= $threshold['max']) {
                return [
                    'type' => $threshold['type'],
                    'deduction_hours' => $threshold['deduction_hours'],
                ];
            }
        }

        // Default: full day absent
        return [
            'type' => 'absent',
            'deduction_hours' => 8,
        ];
    }

    /**
     * Record late deduction for a time log
     *
     * @param TimeLog $timeLog
     * @param array $deductionData
     * @return LateDeduction
     */
    public function recordLateDeduction(TimeLog $timeLog, array $deductionData)
    {
        // Check if deduction already exists for this time log
        $existing = LateDeduction::where('time_log_id', $timeLog->id)->first();

        $lateDeduction = $existing ?: new LateDeduction();

        $lateDeduction->fill([
            'time_log_id' => $timeLog->id,
            'employee_id' => $timeLog->employee_id,
            'attendance_date' => $timeLog->log_date,
            'expected_time' => $deductionData['expected_time'],
            'actual_time' => $deductionData['actual_time'],
            'late_minutes' => $deductionData['late_minutes'],
            'deduction_type' => $deductionData['deduction_type'],
            'deduction_hours' => $deductionData['deduction_hours'],
            'hourly_rate' => $deductionData['hourly_rate'],
            'deduction_amount' => $deductionData['deduction_amount'],
        ]);

        $lateDeduction->save();

        return $lateDeduction;
    }

    /**
     * Process attendance and create/update late deduction record
     *
     * @param Employee $employee
     * @param Carbon $attendanceDate
     * @param Carbon $clockInTime
     * @return LateDeduction|null
     */
    public function processAttendanceLate(Employee $employee, Carbon $attendanceDate, Carbon $clockInTime)
    {
        // Get employee's shift for this date
        $shift = $this->getEmployeeShiftForDate($employee, $attendanceDate);

        if (!$shift) {
            return null;
        }

        // Calculate deduction
        $deductionData = $this->calculateLateDeduction($employee, $shift, $clockInTime, $attendanceDate);

        // Only record if actually late
        if ($deductionData['late_minutes'] === 0) {
            return null;
        }

        // Find or create time log
        $timeLog = TimeLog::where('employee_id', $employee->id)
            ->where('log_date', $attendanceDate)
            ->first();

        if ($timeLog) {
            return $this->recordLateDeduction($timeLog, $deductionData);
        }

        return null;
    }

    /**
     * Get employee's hourly rate for a specific date
     *
     * @param Employee $employee
     * @param Carbon $date
     * @return float
     */
    private function getEmployeeHourlyRate(Employee $employee, Carbon $date)
    {
        // Get active salary record for this date
        $salary = SalaryRecord::where('employee_id', $employee->id)
            ->where('effective_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->latest('effective_date')
            ->first();

        if (!$salary) {
            return 0;
        }

        // Convert salary to hourly rate
        // Assuming salary is monthly and we use 22 working days, 8 hours/day
        $dailyRate = $salary->amount / 22;
        $hourlyRate = $dailyRate / 8;

        return round($hourlyRate, 2);
    }

    /**
     * Get employee's active shift for a date
     * (Uses ShiftService pattern)
     *
     * @param Employee $employee
     * @param Carbon $date
     * @return Shift|null
     */
    private function getEmployeeShiftForDate(Employee $employee, Carbon $date)
    {
        $shiftService = new ShiftService();
        return $shiftService->getEmployeeShiftForDate($employee, $date);
    }

    /**
     * Get late deductions for employee over period
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEmployeeLateDeductions(Employee $employee, Carbon $startDate, Carbon $endDate)
    {
        return LateDeduction::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->get();
    }

    /**
     * Calculate total late deductions for payroll period
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function calculateTotalLateDeductions(Employee $employee, Carbon $startDate, Carbon $endDate)
    {
        $deductions = $this->getEmployeeLateDeductions($employee, $startDate, $endDate)
            ->filter(fn($d) => !$d->is_excused);

        return [
            'total_late_instances' => $deductions->count(),
            'total_deduction_hours' => round($deductions->sum('deduction_hours'), 2),
            'total_deduction_amount' => round($deductions->sum('deduction_amount'), 2),
            'by_type' => $deductions->groupBy('deduction_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_hours' => $group->sum('deduction_hours'),
                    'total_amount' => round($group->sum('deduction_amount'), 2),
                ];
            }),
        ];
    }

    /**
     * Excuse a late deduction (waive the penalty)
     *
     * @param LateDeduction $lateDeduction
     * @param string $reason
     * @param int|null $approvedById
     * @return LateDeduction
     */
    public function excuseLateDeduction(LateDeduction $lateDeduction, $reason, $approvedById = null)
    {
        $lateDeduction->is_excused = true;
        $lateDeduction->excuse_reason = $reason;
        $lateDeduction->approved_by = $approvedById;
        $lateDeduction->save();

        return $lateDeduction;
    }

    /**
     * Get policy configuration
     *
     * @return array
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * Set custom late policy
     *
     * @param array $policy
     * @return void
     */
    public function setPolicy(array $policy)
    {
        $this->policy = array_merge($this->policy, $policy);
    }

    /**
     * Get policy description as human-readable text
     *
     * @return string
     */
    public function getPolicyDescription()
    {
        return <<<'POLICY'
Late Time-In Policy:
- 0-10 minutes: No deduction (grace period)
- 11-30 minutes: 1 hour deduction
- 31-60 minutes: Half day deduction (4 hours)
- 61+ minutes: Absent (full day = 8 hours)

All deductions are applied to the employee's hourly rate.
Deductions can be excused by HR/Management with proper justification.
POLICY;
    }
}
