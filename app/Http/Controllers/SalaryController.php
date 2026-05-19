<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryRecord;
use App\Models\TaxBracket;
use App\Models\DeductionRule;
use App\Models\GovernmentContributionRate;
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
        $governmentContributions = GovernmentContributionRate::orderBy('sort_order')->get();
        $deductionRules = DeductionRule::orderBy('sort_order')->get();

        return view('salary.settings', compact('taxBrackets', 'governmentContributions', 'deductionRules'));
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
     * Save government contribution rates.
     */
    public function saveGovernmentContributions(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contributions' => 'required|array',
            'contributions.*.id' => 'nullable|integer',
            'contributions.*.name' => 'required|string|max:255',
            'contributions.*.employee_rate' => 'required|numeric|min:0|max:100',
            'contributions.*.employer_rate' => 'required|numeric|min:0|max:100',
            'contributions.*.is_active' => 'nullable',
        ]);

        foreach ($validated['contributions'] as $index => $contribData) {
            $payload = [
                'name' => $contribData['name'],
                'employee_rate' => $contribData['employee_rate'] / 100,
                'employer_rate' => $contribData['employer_rate'] / 100,
                'is_active' => isset($contribData['is_active']),
                'sort_order' => $index,
            ];

            if (isset($contribData['id']) && $contribData['id']) {
                GovernmentContributionRate::findOrFail($contribData['id'])->update($payload);
            } else {
                GovernmentContributionRate::create($payload);
            }
        }

        return redirect()->route('salary.settings')->with('success', 'Government contributions updated successfully.');
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
        $employee->load('salaryRecords', 'taxBrackets', 'governmentContributionRates', 'deductionRules');
        $payFrequencies = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];

        $allTaxBrackets = TaxBracket::where('is_active', true)->orderBy('sort_order')->get();
        $allContributions = GovernmentContributionRate::where('is_active', true)->orderBy('sort_order')->get();
        $allDeductionRules = DeductionRule::where('is_active', true)->orderBy('sort_order')->get();

        return view('salary.show', compact(
            'employee', 'payFrequencies',
            'allTaxBrackets', 'allContributions', 'allDeductionRules'
        ));
    }

    /**
     * Show form to create new salary record for an employee.
     */
    public function create(Employee $employee): View
    {
        $payFrequencies = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];

        return view('salary.create', compact('employee', 'payFrequencies'));
    }

    /**
     * Store a new salary record.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'daily_divisor' => 'nullable|numeric|min:1',
            'pay_frequency' => 'required|integer|in:1,2,3,4,5,6',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

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
            $this->syncGovernmentContributionsForEmployeeType($employee);
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

        return view('salary.edit', compact('salaryRecord', 'employee', 'payFrequencies'));
    }

    /**
     * Update a salary record.
     */
    public function update(Request $request, SalaryRecord $salaryRecord): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'daily_divisor' => 'nullable|numeric|min:1',
            'pay_frequency' => 'required|integer|in:1,2,3,4,5,6',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

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
     * Save per-employee tax bracket, contribution, and deduction rule assignments.
     */
    public function saveAssignments(Request $request, Employee $employee): RedirectResponse
    {
        $taxBracketId = $request->input('tax_bracket_id');
        $employee->taxBrackets()->sync($taxBracketId ? [$taxBracketId] : []);
        $employee->governmentContributionRates()->sync($request->input('contributions', []));
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

    private function syncGovernmentContributionsForEmployeeType(Employee $employee): void
    {
        if (in_array((int) $employee->employment_type, [3, 4], true)) {
            return;
        }

        $contributionIds = GovernmentContributionRate::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $employee->governmentContributionRates()->sync($contributionIds);
    }
}
