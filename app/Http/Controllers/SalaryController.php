<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryRecord;
use App\Models\TaxBracket;
use App\Models\DeductionRule;
use App\Models\GovernmentPremium;
use App\Models\GovernmentPremiumBracket;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    /**
     * Display salary records for all employees.
     */
    public function index(): View
    {
        $employees = Employee::with('salaryRecords', 'position')
            ->get()
            ->sortBy('full_name')
            ->values();

        return view('salary.index', compact('employees'));
    }

    /**
     * Show salary deduction and tax settings.
     */
    public function settings(): View
    {
        $taxBrackets = TaxBracket::orderBy('sort_order')->orderBy('threshold')->get();
        $deductionRules = DeductionRule::orderBy('sort_order')->get();
        $lateDeductionRules = \App\Models\LateDeductionRule::orderBy('sort_order')->get();

        $global = \App\Models\PayrollSetting::first();

        return view('salary.settings', compact('taxBrackets', 'deductionRules', 'lateDeductionRules', 'global'));
    }

    /**
     * Show government premium rules.
     */
    public function governmentPremiums(): View
    {
        $governmentPremiums = GovernmentPremium::with('brackets')->orderBy('sort_order')->get();
        $employeeFixedTotal = $governmentPremiums
            ->where('is_active', true)
            ->where('calculation_type', 'Fixed')
            ->sum('employee_value');
        $employerFixedTotal = $governmentPremiums
            ->where('is_active', true)
            ->where('calculation_type', 'Fixed')
            ->sum('employer_value');

        return view('salary.government-premiums', compact('governmentPremiums', 'employeeFixedTotal', 'employerFixedTotal'));
    }

    /**
     * Show official government contribution tables.
     */
    public function contributionTables(): View
    {
        $governmentPremiums = GovernmentPremium::with('brackets')
            ->orderBy('sort_order')
            ->get();

        return view('salary.contribution-tables', compact('governmentPremiums'));
    }

    /**
     * Save rows for one government contribution table (create, update, delete).
     */
    public function saveContributionTable(Request $request, GovernmentPremium $governmentPremium): RedirectResponse
    {
        $validated = $request->validate([
            'brackets' => 'nullable|array',
            'brackets.*.id' => 'nullable|integer',
            'brackets.*.label' => 'nullable|string|max:160',
            'brackets.*.min_compensation' => 'required|numeric|min:0',
            'brackets.*.max_compensation' => 'nullable|numeric|min:0',
            'brackets.*.calculation_type' => 'required|in:Fixed,Percentage',
            'brackets.*.employee_value' => 'required|numeric|min:0',
            'brackets.*.employer_value' => 'required|numeric|min:0',
            'brackets.*.employer_extra_value' => 'required|numeric|min:0',
        ]);

        $brackets = $validated['brackets'] ?? [];

        // Collect IDs that are still present in the form to delete removed ones
        $submittedIds = collect($brackets)->pluck('id')->filter()->all();
        GovernmentPremiumBracket::where('government_premium_id', $governmentPremium->id)
            ->whereNotIn('id', $submittedIds)
            ->delete();

        foreach ($brackets as $index => $bracketData) {
            $payload = [
                'government_premium_id' => $governmentPremium->id,
                'label' => $bracketData['label'] ?? null,
                'min_compensation' => $bracketData['min_compensation'],
                'max_compensation' => $bracketData['max_compensation'] ?? null,
                'calculation_type' => $bracketData['calculation_type'],
                'employee_value' => $bracketData['employee_value'],
                'employer_value' => $bracketData['employer_value'],
                'employer_extra_value' => $bracketData['employer_extra_value'],
                'sort_order' => $index,
            ];

            if (!empty($bracketData['id'])) {
                GovernmentPremiumBracket::where('government_premium_id', $governmentPremium->id)
                    ->findOrFail($bracketData['id'])
                    ->update($payload);
            } else {
                GovernmentPremiumBracket::create($payload);
            }
        }

        return redirect()->route('salary.contribution-tables')->with('success', $governmentPremium->name . ' table updated successfully.');
    }

    /**
     * Save government premium rules.
     */
    public function saveGovernmentPremiums(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'premiums' => 'nullable|array',
            'premiums.*.id' => 'nullable|integer',
            'premiums.*.name' => 'required|string|max:120',
            'premiums.*.calculation_type' => 'required|in:Fixed,Percentage',
            'premiums.*.basis' => 'required|in:Gross Pay,Taxable Pay',
            'premiums.*.employee_value' => 'required|numeric|min:0',
            'premiums.*.employer_value' => 'required|numeric|min:0',
            'premiums.*.description' => 'nullable|string',
            'premiums.*.is_taxable' => 'nullable',
            'premiums.*.is_active' => 'nullable',
        ]);

        $premiums = $validated['premiums'] ?? [];
        $premiumIds = collect($premiums)->pluck('id')->filter()->all();
        GovernmentPremium::whereNotIn('id', $premiumIds)->delete();

        foreach ($premiums as $index => $premiumData) {
            $payload = [
                'name' => $premiumData['name'],
                'calculation_type' => $premiumData['calculation_type'],
                'basis' => $premiumData['basis'],
                'employee_value' => $premiumData['employee_value'],
                'employer_value' => $premiumData['employer_value'],
                'description' => $premiumData['description'] ?? null,
                'is_taxable' => isset($premiumData['is_taxable']),
                'is_active' => isset($premiumData['is_active']),
                'sort_order' => $index,
            ];

            if (isset($premiumData['id']) && $premiumData['id']) {
                GovernmentPremium::findOrFail($premiumData['id'])->update($payload);
            } else {
                GovernmentPremium::create($payload);
            }
        }

        return redirect()->route('salary.government-premiums')->with('success', 'Government premiums updated successfully.');
    }

    /**
     * Save dynamic late deduction rules.
     */
    public function saveLateDeductionRules(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rules' => 'required|array',
            'rules.*.id' => 'nullable|integer',
            'rules.*.name' => 'required|string|max:255',
            'rules.*.max_minutes' => 'required|integer|min:0',
            'rules.*.deduction_hours' => 'required|numeric|min:0',
        ]);

        $ruleIds = collect($validated['rules'])->pluck('id')->filter()->all();
        \App\Models\LateDeductionRule::whereNotIn('id', $ruleIds)->delete();

        foreach ($validated['rules'] as $index => $ruleData) {
            $payload = [
                'name' => $ruleData['name'],
                'max_minutes' => (int) $ruleData['max_minutes'],
                'deduction_hours' => (float) $ruleData['deduction_hours'],
                'sort_order' => $index,
            ];

            if (isset($ruleData['id']) && $ruleData['id']) {
                \App\Models\LateDeductionRule::findOrFail($ruleData['id'])->update($payload);
            } else {
                \App\Models\LateDeductionRule::create($payload);
            }
        }

        return redirect()->route('salary.settings')->with('success', 'Late deduction rules updated successfully.');
    }

    /**
     * Save company-wide payroll defaults (attendance multipliers).
     */
    public function savePayrollSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_overtime_multiplier' => 'nullable|numeric|min:0',
            'attendance_night_differential_multiplier' => 'nullable|numeric|min:0',
            'attendance_late_deduction_multiplier' => 'nullable|numeric|min:0',
            'attendance_undertime_deduction_multiplier' => 'nullable|numeric|min:0',
            'attendance_absence_deduction_multiplier' => 'nullable|numeric|min:0',
            'late_grace_period_minutes' => 'nullable|integer|min:0',
            'late_tier1_max_minutes' => 'nullable|integer|min:0',
            'late_tier2_max_minutes' => 'nullable|integer|min:0',
            'late_tier3_max_minutes' => 'nullable|integer|min:0',
            'late_11_15_deduction_hours' => 'nullable|numeric|min:0',
            'late_16_30_deduction_hours' => 'nullable|numeric|min:0',
            'late_31_60_deduction_hours' => 'nullable|numeric|min:0',
            'late_61_plus_deduction_hours' => 'nullable|numeric|min:0',
        ]);

        $payload = [];
        $keys = [
            'attendance_overtime_multiplier',
            'attendance_night_differential_multiplier',
            'attendance_late_deduction_multiplier',
            'attendance_undertime_deduction_multiplier',
            'attendance_absence_deduction_multiplier',
            'late_grace_period_minutes',
            'late_tier1_max_minutes',
            'late_tier2_max_minutes',
            'late_tier3_max_minutes',
            'late_11_15_deduction_hours',
            'late_16_30_deduction_hours',
            'late_31_60_deduction_hours',
            'late_61_plus_deduction_hours',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                $payload[$key] = $validated[$key] ?? $request->input($key);
            }
        }

        $global = \App\Models\PayrollSetting::first();

        if ($global) {
            $global->update($payload);
        } else {
            \App\Models\PayrollSetting::create($payload);
        }

        return redirect()->route('salary.settings')->with('success', 'Payroll defaults updated successfully.');
    }

    /**
     * Save tax brackets.
     */
    public function saveTaxBrackets(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brackets' => 'required|array',
            'brackets.*.id' => 'nullable|integer',
            'brackets.*.threshold' => 'required|numeric|min:0',
            'brackets.*.rate' => 'required|numeric|min:0|max:100',
            'brackets.*.label' => 'nullable|string',
            'brackets.*.is_active' => 'nullable',
        ]);

        foreach ($validated['brackets'] as $index => $bracketData) {
            $payload = [
                'threshold' => $bracketData['threshold'],
                'rate' => $bracketData['rate'] / 100,
                'label' => $bracketData['label'] ?? null,
                'is_active' => isset($bracketData['is_active']),
                'sort_order' => $index,
            ];

            if (isset($bracketData['id']) && $bracketData['id']) {
                TaxBracket::findOrFail($bracketData['id'])->update($payload);
            } else {
                TaxBracket::create($payload);
            }
        }

        return redirect()->route('salary.settings')->with('success', 'Tax brackets updated successfully.');
    }



    /**
     * Save deduction rules.
     */
    public function saveDeductionRules(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rules' => 'required|array',
            'rules.*.id' => 'nullable|integer',
            'rules.*.name' => 'required|string|max:255',
            'rules.*.type' => 'required|in:Fixed,Percentage,Prorated',
            'rules.*.amount' => 'nullable|numeric|min:0',
            'rules.*.rate' => 'nullable|numeric|min:0|max:100',
            'rules.*.scope' => 'nullable|string',
            'rules.*.is_active' => 'nullable',
        ]);

        foreach ($validated['rules'] as $index => $ruleData) {
            if (isset($ruleData['id']) && $ruleData['id']) {
                DeductionRule::findOrFail($ruleData['id'])->update([
                    'name' => $ruleData['name'],
                    'type' => $ruleData['type'],
                    'amount' => $ruleData['amount'] ?? null,
                    'rate' => $ruleData['rate'] ?? null,
                    'scope' => $ruleData['scope'] ?? null,
                    'is_active' => isset($ruleData['is_active']),
                    'sort_order' => $index,
                ]);
            } else {
                DeductionRule::create([
                    'name' => $ruleData['name'],
                    'type' => $ruleData['type'],
                    'amount' => $ruleData['amount'] ?? null,
                    'rate' => $ruleData['rate'] ?? null,
                    'scope' => $ruleData['scope'] ?? null,
                    'is_active' => isset($ruleData['is_active']),
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('salary.settings')->with('success', 'Deduction rules updated successfully.');
    }

    /**
     * Show salary details for a specific employee.
     */
    public function show(Employee $employee): View
    {
        $employee->load('salaryRecords', 'taxBrackets', 'deductionRules');
        $payFrequencies = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];

        $allTaxBrackets = TaxBracket::where('is_active', true)->orderBy('sort_order')->get();
        $allDeductionRules = DeductionRule::where('is_active', true)->orderBy('sort_order')->get();

        $global = \App\Models\PayrollSetting::first();

        return view('salary.show', compact(
            'employee', 'payFrequencies',
            'allTaxBrackets', 'allDeductionRules', 'global'
        ));
    }

    /**
     * Show form to create new salary record for an employee.
     */
    public function create(Employee $employee): View
    {
        $payFrequencies = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];

        $global = \App\Models\PayrollSetting::first();

        return view('salary.create', compact('employee', 'payFrequencies', 'global'));
    }

    /**
     * Store a new salary record.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'daily_divisor' => 'nullable|numeric|min:1',
            'attendance_overtime_multiplier' => 'nullable|numeric|min:0',
            'attendance_night_differential_multiplier' => 'nullable|numeric|min:0',
            'attendance_late_deduction_multiplier' => 'nullable|numeric|min:0',
            'attendance_undertime_deduction_multiplier' => 'nullable|numeric|min:0',
            'attendance_absence_deduction_multiplier' => 'nullable|numeric|min:0',
            'pay_frequency' => 'required|integer|in:1,2,3,4,5,6',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated = $this->normalizeAttendanceMultipliers($validated);

        DB::transaction(function () use ($request, $employee, $validated): void {
            $validated['employee_id'] = $employee->id;
            $validated['created_by'] = $request->user()->id;

            // Ensure pay_frequency is stored as integer
            if (isset($validated['pay_frequency'])) {
                $validated['pay_frequency'] = (int) $validated['pay_frequency'];
            }

            // End any existing active salary record before creating the new one
            $employee->salaryRecords()
                ->whereNull('end_date')
                ->update(['end_date' => now()->subDay()]);

            $salaryRecord = SalaryRecord::create($validated);

            $this->syncTaxBracketFromSalaryRecord($employee, $salaryRecord);
        });

        return redirect()->route('salary.show', $employee)
            ->with('success', 'Salary record created successfully.');
    }

    /**
     * Show form to edit a salary record.
     */
    public function edit(SalaryRecord $salaryRecord): View
    {
        $employee = $salaryRecord->employee;
        $payFrequencies = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];

        $global = \App\Models\PayrollSetting::first();

        return view('salary.edit', compact('salaryRecord', 'employee', 'payFrequencies', 'global'));
    }

    /**
     * Update a salary record.
     */
    public function update(Request $request, SalaryRecord $salaryRecord): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'daily_divisor' => 'nullable|numeric|min:1',
            'attendance_overtime_multiplier' => 'nullable|numeric|min:0',
            'attendance_night_differential_multiplier' => 'nullable|numeric|min:0',
            'attendance_late_deduction_multiplier' => 'nullable|numeric|min:0',
            'attendance_undertime_deduction_multiplier' => 'nullable|numeric|min:0',
            'attendance_absence_deduction_multiplier' => 'nullable|numeric|min:0',
            'pay_frequency' => 'required|integer|in:1,2,3,4,5,6',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated = $this->normalizeAttendanceMultipliers($validated);

        if (isset($validated['pay_frequency'])) {
            $validated['pay_frequency'] = (int) $validated['pay_frequency'];
        }

        $salaryRecord->update($validated);

        return redirect()->route('salary.show', $salaryRecord->employee)
            ->with('success', 'Salary record updated successfully.');
    }

    /**
     * Delete a salary record.
     */
    public function destroy(SalaryRecord $salaryRecord): RedirectResponse
    {
        $employee = $salaryRecord->employee;
        $salaryRecord->delete();

        return redirect()->route('salary.show', $employee)
            ->with('success', 'Salary record deleted successfully.');
    }

    /**
     * Ensure attendance multiplier fields always have explicit defaults.
     */
    private function normalizeAttendanceMultipliers(array $validated): array
    {
        // Allow explicit nulls - only set values when provided and non-empty
        $keys = [
            'attendance_overtime_multiplier',
            'attendance_night_differential_multiplier',
            'attendance_late_deduction_multiplier',
            'attendance_undertime_deduction_multiplier',
            'attendance_absence_deduction_multiplier',
        ];

        foreach ($keys as $k) {
            if (array_key_exists($k, $validated)) {
                // Treat empty string as null (user cleared the override)
                $validated[$k] = $validated[$k] === '' ? null : $validated[$k];
            }
        }

        return $validated;
    }

    /**
     * Save per-employee tax bracket, contribution, and deduction rule assignments.
     */
    public function saveAssignments(Request $request, Employee $employee): RedirectResponse
    {
        $taxBracketId = $request->input('tax_bracket_id');
        $employee->taxBrackets()->sync($taxBracketId ? [$taxBracketId] : []);
        $employee->deductionRules()->sync($request->input('deduction_rules', []));

        return redirect()->route('salary.show', $employee)
            ->with('success', 'Tax & deduction assignments updated successfully.');
    }

    private function syncTaxBracketFromSalaryRecord(Employee $employee, SalaryRecord $salaryRecord): void
    {
        $taxBracket = TaxBracket::where('is_active', true)
            ->where('threshold', '<=', $salaryRecord->amount)
            ->orderByDesc('threshold')
            ->first();

        if ($taxBracket) {
            $employee->taxBrackets()->sync([$taxBracket->id]);
        }
    }


}
