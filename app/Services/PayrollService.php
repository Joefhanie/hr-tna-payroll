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

            // Bonuses
            $bonuses = $options['bonuses'] ?? [];
            $bonusTotal = $this->applyBonuses($payslip, $bonuses);

            // Calculate taxable amount
            $taxable = $gross + $bonusTotal;

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
            $totalDeductions = $tax + $totalGovEmployee + $totalDeductionRules;
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
