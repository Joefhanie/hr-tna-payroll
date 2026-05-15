<?php

namespace App\Services;

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
        $days = $periodEnd->diffInDays($periodStart) + 1;

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
            case 5:
                $periodDaysInMonth = $periodStart->daysInMonth;
                return round($base * ($days / $periodDaysInMonth), 2);
            case 6:
                return round($base * ($days / 365), 2);
            default:
                // fallback: treat as monthly
                return round($base * ($days / $periodStart->daysInMonth), 2);
        }
    }

    /**
     * Simple tax calculation using configurable brackets.
     * Returns tax amount.
     */
    public function calculateTax(float $taxableAmount): float
    {
        // Example progressive brackets (localize these later or move to config)
        $brackets = [
            ["threshold" => 0, "rate" => 0.0],
            ["threshold" => 10000, "rate" => 0.1],
            ["threshold" => 30000, "rate" => 0.15],
            ["threshold" => 70000, "rate" => 0.2],
        ];

        $tax = 0.0;
        $remaining = $taxableAmount;
        for ($i = count($brackets) - 1; $i >= 0; $i--) {
            $threshold = $brackets[$i]['threshold'];
            $rate = $brackets[$i]['rate'];
            if ($taxableAmount > $threshold) {
                $amountInBracket = $taxableAmount - $threshold;
                $tax += $amountInBracket * $rate;
                $taxableAmount = $threshold;
            }
        }

        return round($tax, 2);
    }

    /**
     * Calculate simple government deductions (employee & employer shares).
     */
    public function calculateGovernmentDeductions(float $gross): array
    {
        // Example rates; these should be configurable per-country
        $employeeRate = 0.05; // 5%
        $employerRate = 0.08; // 8%

        return [
            'employee_share' => round($gross * $employeeRate, 2),
            'employer_share' => round($gross * $employerRate, 2),
        ];
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

            $salaryRecord = $this->getSalaryRecordForDate($employee, $periodEnd);
            $gross = 0.0;
            if ($salaryRecord) {
                $gross = $this->computeGrossForPeriod($salaryRecord, $periodStart, $periodEnd);
            }

            $payslip = Payslip::create([
                'pay_run_id' => $payRun->id,
                'employee_id' => $employee->id,
                'gross_pay' => $gross,
                'total_deductions' => 0,
                'net_pay' => 0,
                'currency' => $options['currency'] ?? 'USD',
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

            // Bonuses
            $bonuses = $options['bonuses'] ?? [];
            $bonusTotal = $this->applyBonuses($payslip, $bonuses);

            // Calculate taxable amount
            $taxable = $gross + $bonusTotal;
            $tax = $this->calculateTax($taxable);

            // Government contributions
            $gov = $this->calculateGovernmentDeductions($gross);
            GovernmentContribution::create([
                'payslip_id' => $payslip->id,
                'contribution_type' => 'statutory',
                'employee_share' => $gov['employee_share'],
                'employer_share' => $gov['employer_share'],
            ]);

            $totalDeductions = $tax + $gov['employee_share'];
            $net = round($taxable - $totalDeductions, 2);

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
        $date = Carbon::now();
        $salaryRecord = $this->getSalaryRecordForDate($employee, $date);
        $gross = $salaryRecord ? $salaryRecord->amount : 0.0;

        $bonuses = $options['bonuses'] ?? [];
        $bonusTotal = array_sum(array_map(fn($b) => (float)($b['amount'] ?? 0), $bonuses));

        $tax = $this->calculateTax($gross + $bonusTotal);
        $gov = $this->calculateGovernmentDeductions($gross);

        $totalDeductions = $tax + $gov['employee_share'];
        $net = round($gross + $bonusTotal - $totalDeductions, 2);

        return [
            'gross' => round($gross, 2),
            'bonuses' => round($bonusTotal, 2),
            'tax' => $tax,
            'government' => $gov,
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
     * Finalize an entire pay run by generating payslips for all active employees.
     *
     * PayRun Status: 1=Draft, 2=Processing, 3=Completed, 4=Cancelled
     */
    public function finalizePayRun(PayRun $payRun): void
    {
        $employees = Employee::whereNull('termination_date')->get();
        foreach ($employees as $employee) {
            $this->generatePayslip($payRun, $employee);
        }
        $payRun->status = 3; // Completed
        $payRun->finalized_at = Carbon::now();
        $payRun->save();
    }
}
