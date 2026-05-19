<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\PayslipLineItem;
use App\Models\GovernmentContribution;
use App\Models\SalaryRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Get the active salary record for an employee on a given date.
     */
    public function getSalaryRecordForDate(Employee $employee, Carbon $date = null): ?SalaryRecord
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
        $base = $record->amount;

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
                $dailyRate = $base / $divisor;
                return round($dailyRate * $days, 2);

            case 6:
                return round($base * ($days / 365), 2);
            default:
                // fallback: use the employee's configured divisor
                $divisor = (float) ($record->daily_divisor ?? 21.8);
                return round(($base / $divisor) * $days, 2);
        }
    }

    /**
     * Calculate tax using the employee's assigned tax brackets.
     * Falls back to zero if no brackets are assigned.
     */
    public function calculateTax(float $taxableAmount, Employee $employee): float
    {
        $brackets = $employee->taxBrackets()
            ->where('is_active', true)
            ->orderBy('threshold', 'asc')
            ->get();

        if ($brackets->isEmpty()) {
            return 0.0;
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

        return round($tax, 2);
    }

    /**
     * Calculate government deductions using the employee's assigned contribution rates.
     * Returns an array of individual contributions with names.
     */
    public function calculateGovernmentDeductions(float $gross, Employee $employee): array
    {
        $contributions = $employee->governmentContributionRates()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $items = [];
        foreach ($contributions as $contrib) {
            $items[] = [
                'name' => $contrib->name,
                'employee_share' => round($gross * (float) $contrib->employee_rate, 2),
                'employer_share' => round($gross * (float) $contrib->employer_rate, 2),
            ];
        }

        return $items;
    }

    /**
     * Calculate deductions from the employee's assigned deduction rules.
     * Returns an array of individual deductions with names.
     */
    public function calculateDeductionRules(float $gross, Employee $employee): array
    {
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
     * Apply bonuses/incentives array to create line items and return total bonus amount.
     * Bonuses: array of ['description' => '', 'amount' => float, 'is_taxable' => bool]
     */
    public function applyBonuses(Payslip $payslip, array $bonuses = []): float
    {
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
    public function calculateAttendanceAdjustments(Employee $employee, Carbon $periodStart, Carbon $periodEnd, float $baseGross): array
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

        if ($attendanceRecords->isEmpty()) {
            return $empty;
        }

        $periodDays = max($periodStart->diffInDays($periodEnd) + 1, 1);
        $dailyRate = round($baseGross / $periodDays, 2);
        $hourlyRate = round($dailyRate / 8, 2);

        $summary = [
            'absent_days' => 0,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
            'premium_minutes' => 0,
        ];

        foreach ($attendanceRecords as $attendance) {
            $attendanceDate = Carbon::parse($attendance->attendance_date->toDateString());
            $shiftStart = $attendanceDate->copy()->setTime(8, 0, 0);
            $shiftEnd = $attendanceDate->copy()->setTime(17, 0, 0);
            $premiumStart = $attendanceDate->copy()->setTime(18, 0, 0);
            $premiumEnd = $attendanceDate->copy()->setTime(22, 0, 0);

            $checkIn = $attendance->check_in ? Carbon::parse($attendance->check_in) : null;
            $checkOut = $attendance->check_out ? Carbon::parse($attendance->check_out) : null;
            $isAbsent = (int) $attendance->status === 3 || (!$checkIn && !$checkOut);

            if ($isAbsent) {
                $summary['absent_days']++;
                continue;
            }

            if ($checkIn && $checkIn->gt($shiftStart)) {
                $summary['late_minutes'] += $shiftStart->diffInMinutes($checkIn);
            }

            if ($checkOut && $checkOut->lt($shiftEnd)) {
                $summary['undertime_minutes'] += $checkOut->diffInMinutes($shiftEnd);
            }

            if ($checkOut && $checkOut->gt($shiftEnd)) {
                $summary['overtime_minutes'] += $shiftEnd->diffInMinutes($checkOut);
            }

            if ($checkOut && $checkOut->gt($premiumStart)) {
                $nightStart = $checkIn && $checkIn->gt($premiumStart) ? $checkIn->copy() : $premiumStart->copy();
                $nightEnd = $checkOut->lt($premiumEnd) ? $checkOut->copy() : $premiumEnd->copy();

                if ($nightEnd->gt($nightStart)) {
                    $summary['premium_minutes'] += $nightStart->diffInMinutes($nightEnd);
                }
            }
        }

        $lateDeduction = round(($summary['late_minutes'] / 60) * $hourlyRate, 2);
        $undertimeDeduction = round(($summary['undertime_minutes'] / 60) * $hourlyRate, 2);
        $absenceDeduction = round($summary['absent_days'] * $dailyRate, 2);

        $overtimePay = round(($summary['overtime_minutes'] / 60) * $hourlyRate * 1.25, 2);
        $nightDifferential = round(($summary['premium_minutes'] / 60) * $hourlyRate * 0.10, 2);

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
            $employee->load('taxBrackets', 'governmentContributionRates', 'deductionRules');

            $salaryRecord = $this->getSalaryRecordForDate($employee, $periodEnd);
            $gross = 0.0;
            if ($salaryRecord) {
                $gross = $this->computeGrossForPeriod($salaryRecord, $periodStart, $periodEnd);
            }

            $attendanceAdjustments = $this->calculateAttendanceAdjustments($employee, $periodStart, $periodEnd, $gross);
            $attendanceEarningsTotal = $attendanceAdjustments['earnings_total'];
            $attendanceDeductionsTotal = $attendanceAdjustments['deductions_total'];

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

            // Bonuses
            $bonuses = $options['bonuses'] ?? [];
            $bonusTotal = $this->applyBonuses($payslip, $bonuses);

            // Calculate taxable amount
            $totalGross = round($gross + $attendanceEarningsTotal + $bonusTotal, 2);
            $taxable = $totalGross;

            // Tax — using employee's assigned brackets
            $tax = $this->calculateTax($taxable, $employee);
            if ($tax > 0) {
                PayslipLineItem::create([
                    'payslip_id' => $payslip->id,
                    'component_type' => 3,
                    'description' => 'Income Tax',
                    'amount' => $tax,
                    'is_taxable' => false,
                ]);
            }

            // Government contributions — using employee's assigned rates
            $govItems = $this->calculateGovernmentDeductions($gross, $employee);
            $totalGovEmployee = 0.0;
            foreach ($govItems as $gov) {
                GovernmentContribution::create([
                    'payslip_id' => $payslip->id,
                    'contribution_type' => $gov['name'],
                    'employee_share' => $gov['employee_share'],
                    'employer_share' => $gov['employer_share'],
                ]);
                PayslipLineItem::create([
                    'payslip_id' => $payslip->id,
                    'component_type' => 4,
                    'description' => $gov['name'],
                    'amount' => $gov['employee_share'],
                    'is_taxable' => false,
                ]);
                $totalGovEmployee += $gov['employee_share'];
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
        $employee->load('taxBrackets', 'governmentContributionRates', 'deductionRules');

        $date = Carbon::now();
        $salaryRecord = $this->getSalaryRecordForDate($employee, $date);
        $gross = $salaryRecord ? $salaryRecord->amount : 0.0;

        $bonuses = $options['bonuses'] ?? [];
        $bonusTotal = array_sum(array_map(fn($b) => (float)($b['amount'] ?? 0), $bonuses));

        $tax = $this->calculateTax($gross + $bonusTotal, $employee);
        $govItems = $this->calculateGovernmentDeductions($gross, $employee);
        $deductionItems = $this->calculateDeductionRules($gross, $employee);

        $totalGovEmployee = array_sum(array_map(fn($g) => $g['employee_share'], $govItems));
        $totalDeductionRules = array_sum(array_map(fn($d) => $d['amount'], $deductionItems));

        $totalDeductions = $tax + $totalGovEmployee + $totalDeductionRules;
        $net = round($gross + $bonusTotal - $totalDeductions, 2);

        return [
            'gross' => round($gross, 2),
            'bonuses' => round($bonusTotal, 2),
            'tax' => $tax,
            'government' => $govItems,
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

    /**
     * Generate draft payslips for all active employees to allow previewing.
     */
    public function generateDraftPayRun(PayRun $payRun, array $employeeIds = null): void
    {
        $query = Employee::whereNull('termination_date');
        if ($employeeIds) {
            $query->whereIn('id', $employeeIds);
        }
        $employees = $query->get();

        foreach ($employees as $employee) {
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
