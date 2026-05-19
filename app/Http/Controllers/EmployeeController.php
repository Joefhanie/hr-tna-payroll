<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(): View
    {
        $employees = Employee::with(['department', 'position', 'manager', 'user'])
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        $departments = Department::all();
        $positions = Position::all();
        // Only list active, full-time employees as possible managers
        $managers = Employee::where('status', 1)
            ->where('employment_type', 1)
            ->get();

        $pendingUser = null;
        $pendingUserId = session('pending_employee_user_id');
        if ($pendingUserId) {
            $pendingUser = User::find($pendingUserId);
        }

        return view('employees.create', compact('departments', 'positions', 'managers', 'pendingUser'));
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'unique:employees'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Non-binary,Prefer not to say'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'marital_status' => ['nullable', 'in:Single,Married,Widowed,Divorced,Separated'],
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:80'],
            // employment_type submitted as numeric codes: 1=Full-time,2=Part-time,3=Contract,4=Temporary
            'employment_type' => ['required', 'in:1,2,3,4'],
            // status codes: 1=Active, 2=Probationary, 3=On Leave, 4=Resigned, 5=Terminated
            'status' => ['required', 'in:1,2,3,4,5'],
            'hire_date' => ['required', 'date'],
            'regularization_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'termination_reason' => ['nullable', 'string'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
        ]);

        // Extract role for user update
        $role = $validated['role'] ?? null;
        unset($validated['role']);

        $validated['employee_code'] = $this->generateTemporaryEmployeeCode();

        $employee = Employee::create($validated);
        $employee->update([
            'employee_code' => $this->generateEmployeeCode(
                $employee->first_name,
                $employee->last_name,
                $employee->id,
            ),
        ]);

        $pendingUserId = $request->session()->pull('pending_employee_user_id');
        if ($pendingUserId) {
            $fullName = trim($employee->first_name . ' ' . $employee->middle_name . ' ' . $employee->last_name);
            $fullName = str_replace('  ', ' ', $fullName);
            User::whereKey($pendingUserId)->update([
                'employee_id' => $employee->id,
                'name' => $fullName,
                'role' => $role,
            ]);
        }

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'manager',
            'emergencyContacts',
            'governmentIds',
            'salaryRecords',
            'documents'
        ]);

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): View
    {
        $departments = Department::all();
        $positions = Position::all();
        // Exclude the employee being edited; only active full-time employees
        $managers = Employee::where('id', '!=', $employee->id)
            ->where('status', 1)
            ->where('employment_type', 1)
            ->get();

        return view('employees.edit', compact('employee', 'departments', 'positions', 'managers'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'unique:employees,email,' . $employee->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Non-binary,Prefer not to say'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'marital_status' => ['nullable', 'in:Single,Married,Widowed,Divorced,Separated'],
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:80'],
            // employment_type submitted as numeric codes: 1=Full-time,2=Part-time,3=Contract,4=Temporary
            'employment_type' => ['required', 'in:1,2,3,4'],
            // status codes: 1=Active, 2=Probationary, 3=On Leave, 4=Resigned, 5=Terminated
            'status' => ['required', 'in:1,2,3,4,5'],
            'hire_date' => ['required', 'date'],
            'regularization_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'termination_reason' => ['nullable', 'string'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
        ]);

        $employee->update($validated);

        // Sync the name to the associated User account, if one exists
        $fullName = trim($employee->first_name . ' ' . $employee->middle_name . ' ' . $employee->last_name);
        $fullName = str_replace('  ', ' ', $fullName);
        User::where('employee_id', $employee->id)->update([
            'name' => $fullName,
        ]);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    /**
     * Grant or update role for an employee.
     */
    public function grantRole(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:1,2,4'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        // Only proceed if employee has a user account
        if (!$employee->user) {
            return redirect()->route('employees.index')
                ->with('error', 'Employee does not have a user account.');
        }

        $fromDate = $validated['from_date'] ? \Carbon\Carbon::createFromFormat('Y-m-d', $validated['from_date']) : now();
        $toDate = $validated['to_date'] ? \Carbon\Carbon::createFromFormat('Y-m-d', $validated['to_date']) : null;

        // If no end date, make it permanent
        if (!$toDate) {
            $employee->user->update([
                'role' => $validated['role'],
            ]);

            return redirect()->route('employees.index')
                ->with('success', 'Role granted permanently.');
        }

        // Save temporary assignment into temporary_assignments table
        \App\Models\TemporaryAssignment::create([
            'user_id' => $employee->user->id,
            'temporary_role' => $validated['role'],
            'original_role' => $employee->user->role,
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'is_active' => true,
        ]);

        // Update user's role immediately if from_date is today or earlier
        if ($fromDate->toDateString() <= now()->toDateString()) {
            $employee->user->update([
                'role' => $validated['role'],
            ]);
        }

        return redirect()->route('employees.index')
            ->with('success', 'Temporary role access granted from ' . $fromDate->format('M d, Y') . ' to ' . $toDate->format('M d, Y') . '.');
    }

    /**
     * Generate an employee code from initials and the record id.
     */
    private function generateEmployeeCode(string $firstName, string $lastName, int $id): string
    {
        $firstInitial = strtoupper(substr(trim($firstName), 0, 1));
        $lastInitial = strtoupper(substr(trim($lastName), 0, 1));

        return $firstInitial . $lastInitial . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a temporary unique employee code for the initial insert.
     */
    private function generateTemporaryEmployeeCode(): string
    {
        return 'TMP' . now()->format('YmdHis') . random_int(1000, 9999);
    }
}
