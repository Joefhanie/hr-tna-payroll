<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\GovernmentContribution;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\PayslipDispute;
use App\Models\PayslipLineItem;
use App\Models\PayrollSetting;
use App\Models\GovernmentPremium;
use App\Models\PreviousClaim;
use App\Models\TaxBracket;
use App\Models\SalaryRecord;
use App\Models\EmployeePlotting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollService
{
    /**
     * Get the active salary record for an employee on a given date.
     */
    public function getSalaryRecordForDate(Employee $employee, ?Carbon $date = null): ?SalaryRecord
    {
        $date = $date ?? Carbon::now();
        return $employee->salaryRecords()
            ->where('effective_date', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_date')
            ->first();
    }

    /**
     * Compute gross pay for a salary record across a period.
     */
    public function computeGrossForPeriod(SalaryRecord $record, Carbon $periodStart, Carbon $periodEnd): float
    {
        $days = $periodStart->diffInDays($periodEnd) + 1;

        // pay_frequency uses integers: 1=Hourly, 2=Daily, 3=Weekly, 4=Bi-weekly, 5=Monthly, 6=Annual
        $type = (int) ($record->pay_frequency ?? 3);
        $base = (float) $record->amount;

        switch ($type) {
            case 1:
                return round($base * ($days * 8), 2);
            case 2:
                return round($base * $days, 2);
            case 3:
                return round($base * ($days / 7), 2);
            case 4:
                return round($base * ($days / 14), 2);
            case 5: // Monthly
                // Standard Philippine Semi-Monthly Period (typically 13-17 calendar days)
                if ($days >= 13 && $days <= 17) {
                    return round($base / 2, 2);
                }

                // Standard Full Month Period (typically 28-31 calendar days)
                if ($days >= 28 && $days <= 31) {
                    return round($base, 2);
                }

                // For custom periods (e.g. final pay or mid-cycle hiring)
                // Use the employee's configured daily rate divisor (21.8 for 5-day, 26.1667 for 6-day)
                $divisor = (float) ($record->daily_divisor ?? 21.8);
                if ($divisor <= 0) {
                    $divisor = 21.8;
                }
                $dailyRate = $base / $divisor;
                return round($dailyRate * $days, 2);

            case 6:
                return round($base * ($days / 365), 2);
            default:
                // fallback: use the employee's configured divisor
                $divisor = (float) ($record->daily_divisor ?? 21.8);
                if ($divisor <= 0) {
                    $divisor = 21.8;
                }
                return round(($base / $divisor) * $days, 2);
        }
    }

    /**
     * Count distinct attendance days with a worked status within a pay period.
     */
    private function countWorkedAttendanceDays(Employee $employee, Carbon $periodStart, Carbon $periodEnd): int
    {
        $employee->loadMissing('user');

        if (! $employee->user) {
            return 0;
        }

        return Attendance::where('user_id', $employee->user->id)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereIn('status', [1, 2])
            ->distinct('attendance_date')
            ->count('attendance_date');
    }

    /**
     * Compute the attendance-based base pay for the current pay period.
     */
    private function computeAttendanceBasedGross(SalaryRecord $record, Employee $employee, Carbon $periodStart, Carbon $periodEnd): float
    {
        $baseAmount = (float) $record->amount;
        $divisorValue = (float) ($record->daily_divisor ?? 0);
        $isFixedRate = $divisorValue === 0.0;

        if ($isFixedRate) {
            return round($baseAmount, 2);
        }

        $workedDays = $this->countWorkedAttendanceDays($employee, $periodStart, $periodEnd);

        return match ((int) ($record->pay_frequency ?? 5)) {
            5 =>
                (function () use ($baseAmount, $record, $workedDays) {
                    $divisor = (float) ($record->daily_divisor ?? 21.8);
                    if ($divisor <= 0) {
                        $divisor = 21.8;
                    }
                    return round(($baseAmount / $divisor) * $workedDays, 2);
                })(),
            default => $this->computeGrossForPeriod($record, $periodStart, $periodEnd),
        };
    }

    /**
     * Resolve the active tax bracket that matches a taxable amount.
     */
    public function resolveTaxBracket(float $taxableAmount): ?TaxBracket
    {
        return TaxBracket::resolveForTaxableAmount($taxableAmount);
    }

    /**
     * Calculate tax from a provided bracket collection.
     */
    public function calculateTaxBreakdownFromBrackets(iterable $brackets, float $taxableAmount): array
    {
        $brackets = collect($brackets)->values();

        if ($brackets->isEmpty()) {
            return [
                'tax' => 0.0,
                'bracket' => null,
            ];
        }

        $tax = 0.0;
        $remaining = $taxableAmount;

        // Progressive calculation: for each bracket, tax the portion above its threshold
        for ($i = $brackets->count() - 1; $i >= 0; $i--) {
            $threshold = (float) $brackets[$i]->threshold;
            $rate = (float) $brackets[$i]->rate;

            if ($remaining > $threshold) {
                $amountInBracket = $remaining - $threshold;
                $tax += $amountInBracket * $rate;
                $remaining = $threshold;
            }
        }

        $resolvedBracket = $brackets
            ->filter(function ($bracket) use ($taxableAmount) {
                return $taxableAmount >= (float) $bracket->threshold;
            })
            ->sortByDesc('threshold')
            ->first();

        return [
            'tax' => round($tax, 2),
            'bracket' => $resolvedBracket,
        ];
    }

    /**
     * Calculate tax using the active tax brackets that match taxable income.
     * Returns both the computed tax and the resolved bracket.
     */
    public function calculateTaxBreakdown(float $taxableAmount): array
    {
        return $this->calculateTaxBreakdownFromBrackets(TaxBracket::getActiveBrackets(), $taxableAmount);
    }

    /**
     * Calculate tax using the active tax brackets that match taxable income.
     */
    public function calculateTax(float $taxableAmount, Employee $employee): float
    {
        return $this->calculateTaxBreakdown($taxableAmount)['tax'];
    }



    /**
     * Calculate deductions from the employee's assigned deduction rules.
     * Returns an array of individual deductions with names.
     */
    public function calculateDeductionRules(float $gross, Employee $employee): array
    {
        // Fixed-rate employees should not receive deductions from deduction rules
        $activeSalary = $this->getSalaryRecordForDate($employee);
        $isFixedRate = $activeSalary && ((float) ($activeSalary->daily_divisor ?? 0) === 0.0);

        if ($isFixedRate) {
            return [];
        }

        $rules = $employee->deductionRules()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $items = [];
        foreach ($rules as $rule) {
            $amount = 0.0;
            switch ($rule->type) {
                case 'Fixed':
                    $amount = (float) $rule->amount;
                    break;
                case 'Percentage':
                    $amount = round($gross * ((float) $rule->amount / 100), 2);
                    break;
                case 'Prorated':
                    $amount = (float) $rule->amount; // Prorated logic can be refined later
                    break;
            }

            if ($amount > 0) {
                $items[] = [
                    'name' => $rule->name,
                    'amount' => $amount,
                ];
            }
        }

        return $items;
    }

    /**
     * Calculate active government premium deductions and employer shares.
     */
    public function calculateGovernmentPremiums(float $gross, float $taxable, float $monthlyCompensation): array
    {
        $premiums = GovernmentPremium::active();
        $items = [];

        foreach ($premiums as $premium) {
            $bracket = $premium->brackets
                ->first(function ($row) use ($monthlyCompensation) {
                    $min = (float) $row->min_compensation;
                    $max = $row->max_compensation === null ? null : (float) $row->max_compensation;

                    return $monthlyCompensation >= $min && ($max === null || $monthlyCompensation <= $max);
                });

            if ($bracket) {
                $basisAmount = match ($bracket->basis) {
                    'Taxable Pay' => $taxable,
                    'Gross Pay' => $gross,
                    default => $monthlyCompensation,
                };
                $calculationType = $bracket->calculation_type;
                $employeeValue = (float) $bracket->employee_value;
                $employerValue = (float) $bracket->employer_value;
                $employerExtraValue = (float) $bracket->employer_extra_value;
            } else {
                $basisAmount = $premium->basis === 'Taxable Pay' ? $taxable : $gross;
                $calculationType = $premium->calculation_type;
                $employeeValue = (float) $premium->employee_value;
                $employerValue = (float) $premium->employer_value;
                $employerExtraValue = 0.0;
            }

            if ($calculationType === 'Percentage') {
                $employeeShare = round($basisAmount * ($employeeValue / 100), 2);
                $employerShare = round($basisAmount * ($employerValue / 100), 2);
            } else {
                $employeeShare = round($employeeValue, 2);
                $employerShare = round($employerValue, 2);
            }

            $employerShare = round($employerShare + $employerExtraValue, 2);

            if ($employeeShare > 0 || $employerShare > 0) {
                $items[] = [
                    'name' => $premium->name,
                    'employee_share' => $employeeShare,
                    'employer_share' => $employerShare,
                    'is_taxable' => (bool) $premium->is_taxable,
                    'matched_bracket' => $bracket?->label,
                ];
            }
        }

        return $items;
    }

    /**
     * Apply bonuses/incentives array to create line items and return total bonus amount.
     * Bonuses: array of ['description' => '', 'amount' => float, 'is_taxable' => bool]
     */
    public function applyBonuses(Payslip $payslip, array $bonuses = []): float
    {
        // Fixed-rate employees should not receive bonuses
        $employee = $payslip->employee;
        $periodStart = $payslip->payRun?->period_start ?? null;
        $salaryRecord = null;
        if ($employee) {
            $salaryRecord = $this->getSalaryRecordForDate($employee, $periodStart);
        }

        $isFixedRate = $salaryRecord && ((float) ($salaryRecord->daily_divisor ?? 0) === 0.0);
        if ($isFixedRate) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($bonuses as $b) {
            $amount = (float) ($b['amount'] ?? 0);
            if ($amount == 0) {
                continue;
            }
            PayslipLineItem::create([
                'payslip_id' => $payslip->id,
                'component_type' => 1,
                'description' => $b['description'] ?? 'Bonus',
                'amount' => $amount,
                'is_taxable' => $b['is_taxable'] ?? true,
            ]);
            $total += $amount;
        }
        return round($total, 2);
    }

    /**
     * Calculate attendance-based bonuses and deductions for a pay period.
     */
    public function calculateAttendanceAdjustments(Employee $employee, Carbon $periodStart, Carbon $periodEnd, float $baseGross, ?SalaryRecord $salaryRecord = null, bool $proratedGross = false): array
    {
        $employee->loadMissing('user');

        $empty = [
            'earnings' => [],
            'deductions' => [],
            'earnings_total' => 0.0,
            'deductions_total' => 0.0,
            'summary' => [
                'absent_days' => 0,
                'late_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_minutes' => 0,
                'premium_minutes' => 0,
            ],
        ];

        if (!$employee->user) {
            return $empty;
        }

        $attendanceRecords = Attendance::where('user_id', $employee->user->id)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('attendance_date')
            ->get();

        // Fetch all approved leave requests and their paid/unpaid status overlapping this period
        $leaves = DB::table('leave_requests')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('leave_requests.employee_id', $employee->id)
            ->where('leave_requests.status', 2) // Approved
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('leave_requests.start_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                  ->orWhereBetween('leave_requests.end_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                  ->orWhere(function ($q2) use ($periodStart, $periodEnd) {
                      $q2->where('leave_requests.start_date', '<=', $periodStart->toDateString())
                         ->where('leave_requests.end_date', '>=', $periodEnd->toDateString());
                  });
            })
            ->select('leave_requests.start_date', 'leave_requests.end_date', 'leave_types.is_paid')
            ->get();

        if ($attendanceRecords->isEmpty()) {
            return $empty;
        }

        $periodDays = max($periodStart->diffInDays($periodEnd) + 1, 1);
        $dailyRate = round($baseGross / $periodDays, 2);

        // Get active shift to determine scheduled daily working hours (default to 8)
        $shift = $employee->getActiveShiftForDate($periodStart);
        $workingHoursPerDay = 8.0;
        if ($shift) {
            $workingHoursPerDay = max(0.1, $shift->getWorkingHoursPerDay());
        }
        $hourlyRate = round($dailyRate / $workingHoursPerDay, 2);

        $global = PayrollSetting::first();

        $overtimeMultiplier = $salaryRecord && $salaryRecord->attendance_overtime_multiplier !== null
            ? (float) $salaryRecord->attendance_overtime_multiplier
            : (float) ($global->attendance_overtime_multiplier ?? 1.25);

        $nightDifferentialMultiplier = $salaryRecord && $salaryRecord->attendance_night_differential_multiplier !== null
            ? (float) $salaryRecord->attendance_night_differential_multiplier
            : (float) ($global->attendance_night_differential_multiplier ?? 0.10);

        $lateDeductionMultiplier = $salaryRecord && $salaryRecord->attendance_late_deduction_multiplier !== null
            ? (float) $salaryRecord->attendance_late_deduction_multiplier
            : (float) ($global->attendance_late_deduction_multiplier ?? 1.00);

        $undertimeDeductionMultiplier = $salaryRecord && $salaryRecord->attendance_undertime_deduction_multiplier !== null
            ? (float) $salaryRecord->attendance_undertime_deduction_multiplier
            : (float) ($global->attendance_undertime_deduction_multiplier ?? 1.00);

        $absenceDeductionMultiplier = $salaryRecord && $salaryRecord->attendance_absence_deduction_multiplier !== null
            ? (float) $salaryRecord->attendance_absence_deduction_multiplier
            : (float) ($global->attendance_absence_deduction_multiplier ?? 1.00);

        $summary = [
            'absent_days' => 0,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
            'premium_minutes' => 0,
        ];

        $totalLateDeductionHours = 0.0;

        $cursor = $periodStart->copy();
        while ($cursor->lte($periodEnd)) {
            $attendanceDate = $cursor->copy();
            $dateStr = $attendanceDate->toDateString();
            $dayOfWeek = $attendanceDate->format('D');

            // Find if there is an attendance record for this day
            $attendance = $attendanceRecords->first(function ($a) use ($dateStr) {
                $aDate = $a->attendance_date instanceof Carbon ? $a->attendance_date : Carbon::parse($a->attendance_date);
                return $aDate->toDateString() === $dateStr;
            });

            // Fetch shift dynamically per attendance date
            $dayShift = $attendance?->shift ?? $employee->getActiveShiftForDate($attendanceDate);

            // Is the employee scheduled to work on this day?
            $isScheduledWorkDay = $dayShift && is_array($dayShift->days_of_week) && in_array($dayOfWeek, $dayShift->days_of_week);

            // Check if this date has an approved leave request
            $approvedLeave = $leaves->first(function ($leave) use ($dateStr) {
                return $dateStr >= $leave->start_date && $dateStr <= $leave->end_date;
            });

            if ($attendance) {
                if ($approvedLeave) {
                    if ((int) $approvedLeave->is_paid === 1) {
                        // Paid leave: no deduction, skip late and undertime calculation for this day
                        $cursor->addDay();
                        continue;
                    } else {
                        // Unpaid leave: counts as absent (1 full day deduction), skip late and undertime
                        $summary['absent_days']++;
                        $cursor->addDay();
                        continue;
                    }
                }

                if ($dayShift) {
                    $shiftStart = $dayShift->getShiftStartDateTime($attendanceDate);
                    $shiftEnd = $dayShift->getShiftEndDateTime($attendanceDate);
                } else {
                    $shiftStart = $attendanceDate->copy()->setTime(8, 0, 0);
                    $shiftEnd = $attendanceDate->copy()->setTime(17, 0, 0);
                }

                $premiumStart = $attendanceDate->copy()->setTime(18, 0, 0);
                $premiumEnd = $attendanceDate->copy()->setTime(22, 0, 0);

                $checkIn = null;
                if ($attendance->check_in) {
                    $timeStr = $attendance->check_in instanceof Carbon ? $attendance->check_in->format('H:i:s') : Carbon::parse($attendance->check_in)->format('H:i:s');
                    $checkIn = Carbon::parse($attendanceDate->toDateString() . ' ' . $timeStr);
                }

                $checkOut = null;
                if ($attendance->check_out) {
                    $timeStr = $attendance->check_out instanceof Carbon ? $attendance->check_out->format('H:i:s') : Carbon::parse($attendance->check_out)->format('H:i:s');
                    $checkOut = Carbon::parse($attendanceDate->toDateString() . ' ' . $timeStr);
                }

                if ($checkIn && $checkOut && $checkOut->lt($checkIn) && $dayShift?->crosses_midnight) {
                    $checkOut->addDay();
                }

                $isAbsent = (int) $attendance->status === 3 || (!$checkIn && !$checkOut);

                if ($isAbsent) {
                    if ($approvedLeave) {
                        if ((int) $approvedLeave->is_paid === 1) {
                            // It is a paid leave, so no absence deduction is applied
                            $cursor->addDay();
                            continue;
                        } else {
                            // It is an unpaid leave, so absence deduction is applied
                            $summary['absent_days']++;
                            $cursor->addDay();
                            continue;
                        }
                    }

                    if ($isScheduledWorkDay) {
                        $summary['absent_days']++;
                    }
                    $cursor->addDay();
                    continue;
                }

                // For flexible shifts, recalculate effective start/end based on actual clock-in.
                // The employee is not late if they clock in within the flexible window.
                // Their shift end is pushed forward to match their actual start + shift duration.
                $effectiveShiftStart = $shiftStart->copy();
                $effectiveShiftEnd   = $shiftEnd->copy();

                if ($dayShift && $dayShift->is_flexible && $checkIn) {
                    $shiftStartMinutes      = $dayShift->getStartMinutes();
                    $flexibleUntilMinutes   = $dayShift->getFlexibleUntilMinutes();
                    $clockInMinutes         = $checkIn->hour * 60 + $checkIn->minute;

                    // Clamp the effective start to [shiftStart, flexUntil]
                    $effectiveStartMinutes  = max($shiftStartMinutes, min($clockInMinutes, $flexibleUntilMinutes));
                    $effectiveShiftStart    = $attendanceDate->copy()->setTime(
                        intdiv($effectiveStartMinutes, 60),
                        $effectiveStartMinutes % 60,
                        0
                    );

                    // Shift end = effective start + original shift duration
                    $endMinutes = $effectiveStartMinutes + $dayShift->shift_duration_minutes;
                    if ($endMinutes >= 24 * 60) {
                        $endMinutes -= 24 * 60;
                        $effectiveShiftEnd = $attendanceDate->copy()->addDay()->setTime(
                            intdiv($endMinutes, 60),
                            $endMinutes % 60,
                            0
                        );
                    } else {
                        $effectiveShiftEnd = $attendanceDate->copy()->setTime(
                            intdiv($endMinutes, 60),
                            $endMinutes % 60,
                            0
                        );
                    }
                }

                // Late check: only fire if employee arrived AFTER the effective start
                if ($checkIn && $checkIn->gt($effectiveShiftStart)) {
                    $dayLateMinutes = $effectiveShiftStart->diffInMinutes($checkIn);
                    $summary['late_minutes'] += $dayLateMinutes;

                    // Apply late policy thresholds from LateDeductionService
                    $lateDeductionService = app(\App\Services\LateDeductionService::class);
                    $deduction = $lateDeductionService->getDeductionForLateMinutes($dayLateMinutes);
                    $totalLateDeductionHours += (float) ($deduction['deduction_hours'] ?? 0.0);
                }

                if ($checkOut && $checkIn && $checkOut->gte($checkIn)) {
                    if ($checkOut->lt($effectiveShiftEnd)) {
                        $summary['undertime_minutes'] += $checkOut->diffInMinutes($effectiveShiftEnd);
                    }

                    if ($checkOut->gt($effectiveShiftEnd)) {
                        $summary['overtime_minutes'] += $effectiveShiftEnd->diffInMinutes($checkOut);
                    }

                    if ($checkOut->gt($premiumStart)) {
                        $nightStart = $checkIn->gt($premiumStart) ? $checkIn->copy() : $premiumStart->copy();
                        $nightEnd = $checkOut->lt($premiumEnd) ? $checkOut->copy() : $premiumEnd->copy();

                        if ($nightEnd->gt($nightStart)) {
                            $summary['premium_minutes'] += $nightStart->diffInMinutes($nightEnd);
                        }
                    }
                }
            } else {
                // If there's no attendance record for this day:
                // Only count as absent if it's a scheduled work day and not an approved paid leave
                if ($isScheduledWorkDay) {
                    if ($approvedLeave) {
                        if ((int) $approvedLeave->is_paid === 1) {
                            // Paid leave: no deduction
                        } else {
                            // Unpaid leave: deduction applies
                            $summary['absent_days']++;
                        }
                    } else {
                        // Unscheduled absence: deduction applies
                        $summary['absent_days']++;
                    }
                }
            }

            $cursor->addDay();
        }

        $activeSalary = $salaryRecord ?? $this->getSalaryRecordForDate($employee, $periodStart);
        $isFixedRate = $activeSalary && ((float) ($activeSalary->daily_divisor ?? 0) === 0.0);

        // Always compute attendance-based deductions; keep proratedGross behavior for absence
        $lateDeduction = round($totalLateDeductionHours * $hourlyRate * $lateDeductionMultiplier, 2);
        $undertimeDeduction = round(($summary['undertime_minutes'] / 60) * $hourlyRate * $undertimeDeductionMultiplier, 2);
        $absenceDeduction = $proratedGross ? 0.0 : round($summary['absent_days'] * $dailyRate * $absenceDeductionMultiplier, 2);

        // Overtime and Night Differential are now ONLY paid via approved requests.
        // We keep the summary minutes for reporting/tracking, but automatic pay is 0.
        $overtimePay = 0.0;
        $nightDifferential = 0.0;

        $earnings = [];
        if ($overtimePay > 0) {
            $earnings[] = ['description' => 'Attendance: Overtime Pay', 'amount' => $overtimePay, 'is_taxable' => true];
        }
        if ($nightDifferential > 0) {
            $earnings[] = ['description' => 'Attendance: Night Differential', 'amount' => $nightDifferential, 'is_taxable' => true];
        }

        $deductions = [];
        if ($lateDeduction > 0) {
            $deductions[] = ['description' => 'Attendance: Late Deduction', 'amount' => $lateDeduction, 'is_taxable' => false];
        }
        if ($undertimeDeduction > 0) {
            $deductions[] = ['description' => 'Attendance: Undertime Deduction', 'amount' => $undertimeDeduction, 'is_taxable' => false];
        }
        if ($absenceDeduction > 0) {
            $deductions[] = ['description' => 'Attendance: Absence Deduction', 'amount' => $absenceDeduction, 'is_taxable' => false];
        }

        return [
            'earnings' => $earnings,
            'deductions' => $deductions,
            'earnings_total' => round($overtimePay + $nightDifferential, 2),
            'deductions_total' => round($lateDeduction + $undertimeDeduction + $absenceDeduction, 2),
            'summary' => $summary,
        ];
    }

    /**
     * Calculate the payable amount for an approved overtime or night differential request.
     */
    public function calculateApprovedRequestAmount(
        PreviousClaim $claim,
        float $gross,
        float $workingHoursPerDay,
        float $overtimeMultiplier,
        float $nightDifferentialMultiplier,
        Carbon $periodStart,
        Carbon $periodEnd
    ): float {
        $claimAmount = (float) $claim->amount;

        if ($claimAmount > 0 || ! in_array($claim->claim_type, ['Overtime', 'Night Differential'], true)) {
            return round($claimAmount, 2);
        }

        if (! $claim->start_time || ! $claim->end_time) {
            return 0.0;
        }

        $start = Carbon::parse($claim->start_time);
        $end = Carbon::parse($claim->end_time);

        if ($end->lt($start)) {
            $end->addDay();
        }

        $minutes = $start->diffInMinutes($end);
        $periodDays = max($periodStart->diffInDays($periodEnd) + 1, 1);
        $dailyRate = round($gross / $periodDays, 2);
        $hourlyRate = round($dailyRate / max($workingHoursPerDay, 0.1), 2);
        $multiplier = $claim->claim_type === 'Overtime'
            ? $overtimeMultiplier
            : $nightDifferentialMultiplier;

        return round(($minutes / 60) * $hourlyRate * $multiplier, 2);
    }

    /**
     * Generate a payslip for an employee within a pay run.
     *
     * Component Type: 1=Earning, 2=Deduction, 3=Tax, 4=Government
     * Payslip Status: 1=Draft, 2=Approved, 3=Released
     */
    public function generatePayslip(PayRun $payRun, Employee $employee, array $options = []): Payslip
    {
        return DB::transaction(function () use ($payRun, $employee, $options) {
            $periodStart = Carbon::parse($payRun->period_start);
            $periodEnd = Carbon::parse($payRun->period_end);

            // Load employee pivot assignments
            $employee->load('deductionRules');

            $salaryRecord = $this->getSalaryRecordForDate($employee, $periodEnd);
            $isFixedRate = $salaryRecord && ((float) ($salaryRecord->daily_divisor ?? 0) === 0.0);
            $applyGovernmentContributions = (bool) ($payRun->deduct_government_contributions ?? false);
            $gross = 0.0;
            if ($salaryRecord) {
                $gross = $this->computeAttendanceBasedGross($salaryRecord, $employee, $periodStart, $periodEnd);
            }

            $attendanceAdjustments = $this->calculateAttendanceAdjustments(
                $employee,
                $periodStart,
                $periodEnd,
                $salaryRecord ? (float) $salaryRecord->amount : $gross,
                $salaryRecord,
                $salaryRecord && ((float) ($salaryRecord->daily_divisor ?? 0) > 0) && (int) ($salaryRecord->pay_frequency ?? 5) === 5
            );
            $attendanceEarningsTotal = $attendanceAdjustments['earnings_total'];
            $attendanceDeductionsTotal = $attendanceAdjustments['deductions_total'];

            // If employee is fixed-rate, ignore attendance earnings/deductions
            if ($isFixedRate) {
                $attendanceAdjustments['earnings'] = [];
                $attendanceAdjustments['deductions'] = [];
                $attendanceEarningsTotal = 0.0;
                $attendanceDeductionsTotal = 0.0;
            }

            $payslip = Payslip::create([
                'pay_run_id' => $payRun->id,
                'employee_id' => $employee->id,
                'gross_pay' => $gross,
                'total_deductions' => 0,
                'net_pay' => 0,
                'currency' => $options['currency'] ?? 'PHP',
                'status' => 1,
            ]);

            // Add base salary line item
            PayslipLineItem::create([
                'payslip_id' => $payslip->id,
                'component_type' => 1,
                'description' => 'Base salary',
                'amount' => $gross,
                'is_taxable' => true,
            ]);

            foreach ($attendanceAdjustments['earnings'] as $earning) {
                PayslipLineItem::create([
                    'payslip_id' => $payslip->id,
                    'component_type' => 1,
                    'description' => $earning['description'],
                    'amount' => $earning['amount'],
                    'is_taxable' => $earning['is_taxable'] ?? true,
                ]);
            }

            foreach ($attendanceAdjustments['deductions'] as $deduction) {
                PayslipLineItem::create([
                    'payslip_id' => $payslip->id,
                    'component_type' => 2,
                    'description' => $deduction['description'],
                    'amount' => $deduction['amount'],
                    'is_taxable' => $deduction['is_taxable'] ?? false,
                ]);
            }

            // Bonuses (manually passed in options)
            $bonuses = $options['bonuses'] ?? [];
            $bonusTotal = $this->applyBonuses($payslip, $bonuses);

            // ── Previous Claims assigned to this pay run ──────────────────────
            // 1. Claims explicitly assigned to this specific pay run
            // 2. Approved claims with no pay run yet ("next pay run") — include
            //    them and stamp them with this pay_run_id so they're not double-counted.
            $previousClaimsTotal = 0.0;

            $previousClaims = PreviousClaim::where('employee_id', $employee->id)
                ->where('status', 2) // Approved
                ->where(function ($q) use ($payRun) {
                    $q->where('pay_run_id', $payRun->id)       // explicitly assigned
                      ->orWhereNull('pay_run_id');             // "next pay run"
                })
                ->get();

            foreach ($previousClaims as $claim) {
                /** @var \App\Models\PreviousClaim $claim */
                $claimAmount = (float) $claim->amount;

                if ($claimAmount == 0 && in_array($claim->claim_type, ['Overtime', 'Night Differential'], true)) {
                    $shift = $employee->getActiveShiftForDate($periodStart);
                    $workingHoursPerDay = $shift ? max(0.1, $shift->getWorkingHoursPerDay()) : 8.0;
                    $global = \App\Models\PayrollSetting::first();
                    $overtimeMultiplier = $salaryRecord && $salaryRecord->attendance_overtime_multiplier !== null
                        ? (float) $salaryRecord->attendance_overtime_multiplier
                        : (float) ($global->attendance_overtime_multiplier ?? 1.25);
                    $nightDifferentialMultiplier = $salaryRecord && $salaryRecord->attendance_night_differential_multiplier !== null
                        ? (float) $salaryRecord->attendance_night_differential_multiplier
                        : (float) ($global->attendance_night_differential_multiplier ?? 0.10);

                    $claimAmount = $this->calculateApprovedRequestAmount(
                        $claim,
                        $gross,
                        $workingHoursPerDay,
                        $overtimeMultiplier,
                        $nightDifferentialMultiplier,
                        $periodStart,
                        $periodEnd
                    );
                }

                if ($claim->claim_type === 'Late Plotted Payment') {
                    $location = 'Unspecified';
                    if (preg_match('/at\s+(.+)$/i', $claim->description, $matches)) {
                        $location = trim($matches[1]);
                    }
                    $description = 'Previous Claim (Plotted Payment): ' . $location
                        . ' (' . \Carbon\Carbon::parse($claim->claim_date)->format('M d, Y') . ')';
                } else {
                    $description = 'Previous Claim: ' . $claim->claim_type
                        . ' (' . \Carbon\Carbon::parse($claim->claim_date)->format('M d, Y') . ')';
                }

                PayslipLineItem::create([
                    'payslip_id'     => $payslip->id,
                    'component_type' => 1, // Earning
                    'description'    => $description,
                    'amount'         => $claimAmount,
                    'is_taxable'     => true,
                ]);
                $previousClaimsTotal += $claimAmount;

                // Stamp "next pay run" claims so they don't appear in future runs
                if (is_null($claim->pay_run_id)) {
                    $claim->pay_run_id = $payRun->id;
                    $claim->save();
                }
            }
            $previousClaimsTotal = round($previousClaimsTotal, 2);

            $approvedDisputesTotal = 0.0;

            $approvedDisputes = PayslipDispute::with('lineItem')
                ->where('employee_id', $employee->id)
                ->where('status', 2)
                ->where(function ($q) use ($payRun) {
                    $q->where('adjustment_pay_run_id', $payRun->id)
                      ->orWhereNull('adjustment_pay_run_id');
                })
                ->get();

            foreach ($approvedDisputes as $dispute) {
                $disputeAmount = round((float) $dispute->dispute_amount, 2);

                if ($disputeAmount <= 0) {
                    continue;
                }

                $referenceLabel = $dispute->lineItem?->description
                    ?: Str::limit($dispute->dispute_reason, 50);

                PayslipLineItem::create([
                    'payslip_id'     => $payslip->id,
                    'component_type' => 1,
                    'description'    => 'Disputes: ' . $referenceLabel,
                    'amount'         => $disputeAmount,
                    'is_taxable'     => true,
                ]);

                $approvedDisputesTotal += $disputeAmount;
                if (is_null($dispute->adjustment_pay_run_id)) {
                    $dispute->adjustment_pay_run_id = $payRun->id;
                    $dispute->adjustment_payslip_id = $payslip->id;
                    $dispute->save();
                }
            }

            $approvedDisputesTotal = round($approvedDisputesTotal, 2);
            // ─────────────────────────────────────────────────────────────────

            // Include any posted plotted payments that fall within this pay period
            $plottedPayments = EmployeePlotting::where('empid', $employee->employee_code)
                ->where('posted', true)
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->get();

            $plottedTotal = 0.0;
            $plottedByLocation = $plottedPayments
                ->groupBy(function ($plotting) {
                    return $plotting->location ?: 'Unspecified';
                })
                ->sortKeys();

            foreach ($plottedByLocation as $location => $records) {
                $locationTotal = round($records->sum(function ($plotting) {
                    return (float) $plotting->amount;
                }), 2);

                if ($locationTotal <= 0) {
                    continue;
                }

                PayslipLineItem::create([
                    'payslip_id' => $payslip->id,
                    'component_type' => 1,
                    'description' => 'Plotted Payment: ' . $location,
                    'amount' => $locationTotal,
                    'is_taxable' => true,
                ]);

                $plottedTotal += $locationTotal;
            }

            $plottedTotal = round($plottedTotal, 2);

            // Calculate taxable amount
            $totalGross = round($gross + $attendanceEarningsTotal + $bonusTotal + $previousClaimsTotal + $approvedDisputesTotal + $plottedTotal, 2);
            $taxable = $totalGross;

            // Tax — skip for fixed-rate employees
            $tax = 0.0;
            if (! $isFixedRate) {
                $taxBreakdown = $this->calculateTaxBreakdown($taxable);
                $tax = $taxBreakdown['tax'];
                if ($tax > 0) {
                    $taxDescription = 'Income Tax';
                    if ($taxBreakdown['bracket']) {
                        $taxDescription .= ' (' . $taxBreakdown['bracket']->label . ')';
                    }

                    PayslipLineItem::create([
                        'payslip_id' => $payslip->id,
                        'component_type' => 3,
                        'description' => $taxDescription,
                        'amount' => $tax,
                        'is_taxable' => false,
                    ]);
                }
            }

            // Government contributions — skip for fixed-rate employees
            $totalGovEmployee = 0.0;
            $monthlyCompensation = $this->estimateMonthlyCompensation($salaryRecord, $gross);
            $premiumItems = (!$isFixedRate && $applyGovernmentContributions)
                ? $this->calculateGovernmentPremiums($gross, $taxable, $monthlyCompensation)
                : [];
            foreach ($premiumItems as $premium) {
                GovernmentContribution::create([
                    'payslip_id' => $payslip->id,
                    'contribution_type' => $premium['name'],
                    'employee_share' => $premium['employee_share'],
                    'employer_share' => $premium['employer_share'],
                ]);
                PayslipLineItem::create([
                    'payslip_id' => $payslip->id,
                    'component_type' => 4,
                    'description' => $premium['name'],
                    'amount' => $premium['employee_share'],
                    'is_taxable' => $premium['is_taxable'],
                ]);
                $totalGovEmployee += $premium['employee_share'];
            }

            // Deduction rules — using employee's assigned rules
            $deductionItems = $this->calculateDeductionRules($gross, $employee);
            $totalDeductionRules = 0.0;
            foreach ($deductionItems as $ded) {
                PayslipLineItem::create([
                    'payslip_id' => $payslip->id,
                    'component_type' => 2,
                    'description' => $ded['name'],
                    'amount' => $ded['amount'],
                    'is_taxable' => false,
                ]);
                $totalDeductionRules += $ded['amount'];
            }

            // Finalize totals
            $totalDeductions = $tax + $totalGovEmployee + $totalDeductionRules + $attendanceDeductionsTotal;
            $net = round($totalGross - $totalDeductions, 2);

            $payslip->gross_pay = $totalGross;
            $payslip->total_deductions = $totalDeductions;
            $payslip->net_pay = $net;
            $payslip->save();

            return $payslip;
        });
    }

    /**
     * Final pay computation (e.g., termination) — simple aggregation.
     */
    public function computeFinalPay(Employee $employee, array $options = []): array
    {
        $employee->load('deductionRules');

        $date = Carbon::now();
        $salaryRecord = $this->getSalaryRecordForDate($employee, $date);
        $isFixedRate = $salaryRecord && ((float) ($salaryRecord->daily_divisor ?? 0) === 0.0);
        $payRun = $options['pay_run'] ?? null;
        $gross = $salaryRecord ? $salaryRecord->amount : 0.0;

        $bonuses = $options['bonuses'] ?? [];
        $bonusTotal = array_sum(array_map(fn($b) => (float)($b['amount'] ?? 0), $bonuses));

        // Skip taxes, premiums, deductions and bonuses for fixed-rate employees
        if ($isFixedRate) {
            $bonusTotal = 0.0;
            $tax = 0.0;
            $taxBreakdown = ['tax' => 0.0, 'bracket' => null];
            $monthlyCompensation = $this->estimateMonthlyCompensation($salaryRecord, $gross);
            $applyGovernmentContributions = false;
            $premiumItems = [];
            $deductionItems = [];
        } else {
            $taxBreakdown = $this->calculateTaxBreakdown($gross + $bonusTotal);
            $tax = $taxBreakdown['tax'];
            $monthlyCompensation = $this->estimateMonthlyCompensation($salaryRecord, $gross);
            $applyGovernmentContributions = $payRun instanceof PayRun
                ? (bool) ($payRun->deduct_government_contributions ?? false)
                : false;
            $premiumItems = $applyGovernmentContributions
                ? $this->calculateGovernmentPremiums($gross, $gross + $bonusTotal, $monthlyCompensation)
                : [];
            $deductionItems = $this->calculateDeductionRules($gross, $employee);
        }

        $totalPremiumEmployee = array_sum(array_map(fn($g) => $g['employee_share'], $premiumItems));
        $totalDeductionRules = array_sum(array_map(fn($d) => $d['amount'], $deductionItems));

        $totalDeductions = $tax + $totalPremiumEmployee + $totalDeductionRules;
        $net = round($gross + $bonusTotal - $totalDeductions, 2);

        return [
            'gross' => round($gross, 2),
            'bonuses' => round($bonusTotal, 2),
            'tax' => $tax,
            'tax_bracket' => $taxBreakdown['bracket']?->label,
            'government_premiums' => $premiumItems,
            'deductions' => $deductionItems,
            'total_deductions' => $totalDeductions,
            'net' => $net,
        ];
    }

    /**
     * Direct deposit stub — integrate with payment provider later.
     */
    public function processDirectDeposit(Payslip $payslip): bool
    {
        // mark as pending/sent depending on provider response
        $payslip->status = 2; // e.g., 2 = completed
        $payslip->save();
        return true;
    }

    private function estimateMonthlyCompensation(?SalaryRecord $record, float $periodGross): float
    {
        if (!$record) {
            return $periodGross;
        }

        $amount = (float) $record->amount;
        $dailyDivisor = (float) ($record->daily_divisor ?? 21.8);
        if ($dailyDivisor <= 0) {
            $dailyDivisor = 21.8;
        }

        return match ((int) ($record->pay_frequency ?? 5)) {
            1 => round($amount * 8 * $dailyDivisor, 2),
            2 => round($amount * $dailyDivisor, 2),
            3 => round($amount * 52 / 12, 2),
            4 => round($amount * 26 / 12, 2),
            5 => round($amount, 2),
            6 => round($amount / 12, 2),
            default => round($periodGross, 2),
        };
    }

    /**
     * Generate draft payslips for all active employees to allow previewing.
     */
    public function generateDraftPayRun(PayRun $payRun, ?array $employeeIds = null): void
    {
        $query = Employee::whereNull('termination_date');
        if ($employeeIds) {
            $query->whereIn('id', $employeeIds);
        }
        $employees = $query->get();

        foreach ($employees as $employee) {
            assert($employee instanceof Employee);
            // Prevent duplicate payslips if regenerated
            if (!Payslip::where('pay_run_id', $payRun->id)->where('employee_id', $employee->id)->exists()) {
                $this->generatePayslip($payRun, $employee);
            }
        }
        $payRun->status = 2; // Processing / Draft Review
        $payRun->save();
    }

    /**
     * Finalize an entire pay run after review.
     *
     * PayRun Status: 1=Draft, 2=Processing, 3=Completed, 4=Cancelled
     */
    public function finalizePayRun(PayRun $payRun): void
    {
        // Approve all payslips
        $payRun->payslips()->update(['status' => 2]); // Payslip Status: 2=Approved

        $payRun->status = 3; // PayRun Status: 3=Completed
        $payRun->finalized_at = Carbon::now();
        $payRun->save();
    }
}
