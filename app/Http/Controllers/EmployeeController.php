<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Services\OnboardingAssignmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function __construct(private readonly OnboardingAssignmentService $onboardingAssignmentService)
    {
    }

    /**
     * Build the query for index and export.
     */
    private function buildQuery(Request $request)
    {
        $query = Employee::with(['department', 'position', 'manager']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('middle_name', 'like', "%{$q}%")
                    ->orWhere('employee_code', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        return $query;
    }

    /**
     * Display a listing of employees.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'q'               => ['nullable', 'string', 'max:255'],
            'status'          => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'employment_type' => ['nullable', 'integer', 'in:1,2,3,4'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $employees = $this->buildQuery($request)
            ->get();

        $departments = Department::all();
        $filters = $request->only(['q', 'status', 'employment_type', 'department_id']);

        return view('employees.index', compact('employees', 'departments', 'filters'));
    }

    /**
     * Export employees to CSV.
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->role !== 4) {
            abort(403, 'Unauthorized action. Exports are restricted to HR only.');
        }

        $request->validate([
            'q'               => ['nullable', 'string', 'max:255'],
            'status'          => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'employment_type' => ['nullable', 'integer', 'in:1,2,3,4'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $employees = $this->buildQuery($request)->get();
        $filename = "employees_export_" . now()->format('Ymd_His') . ".csv";

        $responseHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($employees) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'ID',
                'Employee Code',
                'First Name',
                'Middle Name',
                'Last Name',
                'Email',
                'Phone',
                'Hire Date',
                'Employment Type',
                'Status',
                'Department',
                'Position'
            ]);

            $statusLabels = [
                1 => 'Active',
                2 => 'Probationary',
                3 => 'On Leave',
                4 => 'Resigned',
                5 => 'Terminated'
            ];

            $empLabels = [
                1 => 'Full-time',
                2 => 'Part-time',
                3 => 'Contractual',
                4 => 'Intern'
            ];

            foreach ($employees as $emp) {
                fputcsv($file, [
                    $emp->id,
                    $emp->employee_code,
                    $emp->first_name,
                    $emp->middle_name,
                    $emp->last_name,
                    $emp->email,
                    $emp->phone,
                    optional($emp->hire_date)->toDateString() ?? '',
                    $empLabels[$emp->employment_type] ?? 'N/A',
                    $statusLabels[$emp->status] ?? 'Unknown',
                    $emp->department->name ?? 'N/A',
                    $emp->position->title ?? 'N/A',
                ]);
            }

            fclose($file);
        }, 200, $responseHeaders);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        $departments = Department::all();
        $positions = Position::all();
        $managers = Employee::whereNotNull('manager_id')
            ->orWhere('position_id', 'LIKE', '%Manager%')
            ->get();

        return view('employees.create', compact('departments', 'positions', 'managers'));
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_code' => ['nullable', 'string', 'max:30', 'unique:employees'],
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
            'employment_type' => ['required', 'integer', 'in:1,2,3,4'],
            // status codes: 1=Active, 2=Probationary, 3=On Leave, 4=Resigned/Terminated
            'status' => ['required', 'in:1,2,3,4'],
            'hire_date' => ['required', 'date'],
            'regularization_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'termination_reason' => ['nullable', 'string'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
        ]);

        $employee = DB::transaction(function () use ($request, $validated) {
            $employeeCode = trim((string) ($validated['employee_code'] ?? ''));
            $validated['employee_code'] = $employeeCode !== ''
                ? $employeeCode
                : $this->generateTemporaryEmployeeCode();

            $employee = Employee::create($validated);

            if ($employeeCode === '') {
                $employee->update([
                    'employee_code' => $this->generateEmployeeCode(
                        $employee->first_name,
                        $employee->last_name,
                        $employee->id
                    ),
                ]);
            }

            $pendingUserId = $request->session()->pull('pending_employee_user_id');

            if ($pendingUserId) {
                $user = User::find($pendingUserId);

                if ($user) {
                    $user->update([
                        'employee_id' => $employee->id,
                    ]);
                }
            }

            $this->onboardingAssignmentService->ensureEmployeeIsOnboarded($employee, auth()->id());

            return $employee;
        });

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
        $managers = Employee::where('id', '!=', $employee->id)->get();

        return view('employees.edit', compact('employee', 'departments', 'positions', 'managers'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'unique:employees,employee_code,' . $employee->id],
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
            'employment_type' => ['required', 'integer', 'in:1,2,3,4'],
            // status codes: 1=Active, 2=Probationary, 3=On Leave, 4=Resigned/Terminated
            'status' => ['required', 'in:1,2,3,4'],
            'hire_date' => ['required', 'date'],
            'regularization_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'termination_reason' => ['nullable', 'string'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
        ]);

        $employee->update($validated);

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
     * Terminate an employee by updating their status and termination details.
     */
    public function terminate(Request $request, Employee $employee): RedirectResponse
    {
        abort_if(auth()->user()->role !== 4, 403, 'Only HR can terminate employees.');

        $validated = $request->validate([
            'termination_date' => ['required', 'date'],
            'termination_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $employee->update([
            'status' => 5, // 5 = Terminated
            'termination_date' => $validated['termination_date'],
            'termination_reason' => $validated['termination_reason'] ?? null,
        ]);

        $terminationDateLabel = \Carbon\Carbon::parse($validated['termination_date'])->format('M d, Y');

        return redirect()->route('employees.index')
            ->with('success', 'Employee terminated successfully as of ' . $terminationDateLabel . '.');
    }

    /**
     * Grant or update role for an employee.
     */
    public function grantRole(Request $request, Employee $employee): RedirectResponse
    {
        abort_if(auth()->user()->role !== 4, 403, 'Unauthorized access to temporary access management.');

        $validated = $request->validate([
            'role' => ['required', 'in:1,2,4'],
            'from_date' => ['required', 'date'], // accepts date or datetime-local ISO formats
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        // Only proceed if employee has a user account
        if (!$employee->user) {
            return redirect()->route('employees.index')
                ->with('error', 'Employee does not have a user account.');
        }

        $fromDateInput = $validated['from_date'] ?? null;
        $toDateInput = $validated['to_date'] ?? null;

        $fromDate = $fromDateInput ? \Carbon\Carbon::parse($fromDateInput) : now();
        $toDate = $toDateInput ? \Carbon\Carbon::parse($toDateInput) : now();

        // Treat date-only values as whole-day access windows.
        if (is_string($fromDateInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDateInput)) {
            $fromDate->startOfDay();
        }

        // Temporary access should always remain valid through the selected end date.
        $toDate->setTime(23, 59, 0);

        // Detect if there is an active assignment already (we're editing/updating)
        $hadActive = \App\Models\TemporaryAssignment::where('user_id', $employee->user->id)
            ->where('is_active', true)
            ->exists();

        $latestAssignment = \App\Models\TemporaryAssignment::where('user_id', $employee->user->id)
            ->latest()
            ->first();
        $originalRole = $latestAssignment?->original_role ?? $employee->user->role;

        // Deactivate existing actives
        \App\Models\TemporaryAssignment::where('user_id', $employee->user->id)
            ->update(['is_active' => false]);

        // Save the new temporary assignment as the only active one for this user.
        \App\Models\TemporaryAssignment::create([
            'user_id' => $employee->user->id,
            'temporary_role' => $validated['role'],
            'original_role' => $originalRole,
            'from_date' => $fromDate->toDateTimeString(),
            'to_date' => $toDate->toDateTimeString(),
            'is_active' => true,
            'granted_by' => auth()->id(),
        ]);

        $fromLabel = $fromDate->format('M d, Y' . ($fromDate->format('H:i') !== '00:00' ? ' H:i' : ''));
        $toLabel = $toDate->format('M d, Y' . ($toDate->format('H:i') !== '00:00' ? ' H:i' : ''));

        $roleLabels = [1 => 'Employee', 2 => 'Supervisor', 4 => 'HR'];
        $roleName = $roleLabels[$validated['role']] ?? 'Role';

        if ($hadActive) {
            $message = 'Editing date changed from ' . $fromLabel . ' to ' . $toLabel . '.';
        } else {
            $message = 'Temporary role access granted to ' . $roleName . ' from ' . $fromLabel . ' to ' . $toLabel . '.';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Revoke temporary access for an employee.
     */
    public function revokeRole(Employee $employee): RedirectResponse
    {
        abort_if(auth()->user()->role !== 4, 403, 'Unauthorized access to temporary access management.');

        if (!$employee->user) {
            return redirect()->back()->with('error', 'Employee does not have a user account.');
        }

        // Deactivate all active or scheduled temporary assignments
        \App\Models\TemporaryAssignment::where('user_id', $employee->user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Temporary role access revoked successfully.');
    }

    /**
     * Display the temporary access management page.
     */
    public function temporaryAccess(): \Illuminate\View\View
    {
        abort_if(auth()->user()->role !== 4, 403, 'Unauthorized access to temporary access management.');

        $employees = Employee::whereDoesntHave('user', function ($query) {
                $query->whereIn('role', [2, 4]);
            })
            ->with(['department', 'position', 'user.temporaryAssignments.grantedBy'])
            ->get();

        $allEmployees = Employee::whereDoesntHave('user', function ($query) {
                $query->whereIn('role', [2, 4]);
            })
            ->with(['user'])
            ->get();

        return view('employees.temporary-access', compact('employees', 'allEmployees'));
    }

    /**
     * Display the temporary access details for a specific employee.
     */
    public function showTemporaryAccess(Employee $employee): \Illuminate\View\View
    {
        abort_if(auth()->user()->role !== 4, 403, 'Unauthorized access to temporary access details.');

        $employee->load(['department', 'position', 'user.temporaryAssignments' => function ($query) {
            $query->orderBy('created_at', 'desc')->with('grantedBy');
        }]);

        return view('employees.temporary-access-show', compact('employee'));
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
