<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
     * Show salary details for a specific employee.
     */
    public function show(Employee $employee): View
    {
        $employee->load('salaryRecords');
        $payFrequencies = [1 => 'Weekly', 2 => 'Bi-weekly', 3 => 'Monthly', 4 => 'Annual'];

        return view('salary.show', compact('employee', 'payFrequencies'));
    }

    /**
     * Show form to create new salary record for an employee.
     */
    public function create(Employee $employee): View
    {
        $payFrequencies = [1 => 'Weekly', 2 => 'Bi-weekly', 3 => 'Monthly', 4 => 'Annual'];

        return view('salary.create', compact('employee', 'payFrequencies'));
    }

    /**
     * Store a new salary record.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'salary_type' => 'required|integer|in:1,2,3,4',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['created_by'] = $request->user()->id;

        // Ensure salary_type is stored as integer
        if (isset($validated['salary_type'])) {
            $validated['salary_type'] = (int) $validated['salary_type'];
        }

        // End any existing active salary record
        $employee->salaryRecords()
            ->whereNull('end_date')
            ->update(['end_date' => now()->subDay()]);

        SalaryRecord::create($validated);

        return redirect()->route('salary.show', $employee)
            ->with('success', 'Salary record created successfully.');
    }

    /**
     * Show form to edit a salary record.
     */
    public function edit(SalaryRecord $salaryRecord): View
    {
        $employee = $salaryRecord->employee;
        $payFrequencies = [1 => 'Weekly', 2 => 'Bi-weekly', 3 => 'Monthly', 4 => 'Annual'];

        return view('salary.edit', compact('salaryRecord', 'employee', 'payFrequencies'));
    }

    /**
     * Update a salary record.
     */
    public function update(Request $request, SalaryRecord $salaryRecord): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'salary_type' => 'required|integer|in:1,2,3,4',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['salary_type'])) {
            $validated['salary_type'] = (int) $validated['salary_type'];
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
}
