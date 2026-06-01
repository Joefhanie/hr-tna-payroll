<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Masterlist;
use App\Models\Position;
use App\Models\User;
use App\Services\OnboardingAssignmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'employment_type' => ['nullable', 'integer', 'in:1,2,3,4'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $employees = Employee::with(['department', 'position', 'manager'])->get();

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
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'employment_type' => ['nullable', 'integer', 'in:1,2,3,4'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $employees = $this->buildQuery($request)->get();
        $filename = "employees_export_" . now()->format('Ymd_His') . ".csv";

        $responseHeaders = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($employees) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
                1 => 'Regular',
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

            $masterlistId = $request->session()->pull('pending_masterlist_id');
            $masterlist = null;

            if ($masterlistId) {
                $masterlist = Masterlist::find($masterlistId);
            }

            if (!$masterlist) {
                $masterlist = Masterlist::create([
                    'name' => trim(collect([
                        $validated['first_name'] ?? '',
                        $validated['middle_name'] ?? '',
                        $validated['last_name'] ?? '',
                    ])->filter()->implode(' ')),
                    'contact_number' => (string) ($validated['phone'] ?? ''),
                    'email' => $validated['email'],
                    'emergency_contact' => '',
                    'uid' => null,
                    'is_admin' => 0,
                    'status' => (int) $validated['status'],
                    'created_by' => (int) auth()->id(),
                ]);
            } else {
                $masterlist->update([
                    'name' => trim(collect([
                        $validated['first_name'] ?? '',
                        $validated['middle_name'] ?? '',
                        $validated['last_name'] ?? '',
                    ])->filter()->implode(' ')),
                    'contact_number' => (string) ($validated['phone'] ?? ''),
                    'email' => $validated['email'],
                    'status' => (int) $validated['status'],
                    'updated_by' => (int) auth()->id(),
                ]);
            }

            $validated['masterlist_id'] = $masterlist->id;

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

            $masterlist->update(['emp_id' => $employee->id]);

            $pendingUserId = $request->session()->pull('pending_employee_user_id');

            if ($pendingUserId) {
                $user = User::find($pendingUserId);

                if ($user) {
                    // Link the user to the newly created employee and sync the
                    // user's display name to the employee's full name when the
                    // user's name is blank or currently matches the username.
                    if (empty($user->employee_id)) {
                        $user->employee_id = $employee->id;
                    }

                    $shouldSyncName = empty(trim((string) $user->name)) || ($user->name === $user->username);
                    if ($shouldSyncName) {
                        $user->name = $employee->full_name ?? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                    }

                    $user->save();
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

        $request = request();

        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $filters = $this->temporaryAccessFilters($request);

        $employees = $this->temporaryAccessEmployeesQuery()
            ->with(['department', 'position', 'user.temporaryAssignments.grantedBy'])
            ->get()
            ->filter(fn(Employee $employee) => $this->matchesTemporaryAccessFilters($employee, $filters))
            ->values();

        $allEmployees = $this->temporaryAccessEmployeesQuery()
            ->with(['user'])
            ->get();

        $positions = Position::orderBy('title')->get();
        $departments = Department::orderBy('name')->get();

        return view('employees.temporary-access', compact('employees', 'allEmployees', 'filters', 'positions', 'departments'));
    }

    /**
     * Export temporary access records to CSV.
     */
    public function exportTemporaryAccess(Request $request)
    {
        abort_if(auth()->user()->role !== 4, 403, 'Unauthorized access to temporary access management.');

        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $filters = $this->temporaryAccessFilters($request);

        $employees = $this->temporaryAccessEmployeesQuery()
            ->with(['department', 'position', 'user.temporaryAssignments.grantedBy'])
            ->get()
            ->filter(fn(Employee $employee) => $this->matchesTemporaryAccessFilters($employee, $filters))
            ->values();

        $filename = 'temporary_access_' . now()->format('Ymd_His') . '.csv';

        $responseHeaders = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($employees) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID',
                'Employee Code',
                'Name',
                'Email',
                'Department',
                'Position',
                'From Date',
                'To Date',
                'Status',
                'Base Role',
                'Temporary Role',
                'Granted By',
            ]);

            $roleLabels = [1 => 'Employee', 2 => 'Supervisor', 4 => 'HR'];

            foreach ($employees as $employee) {
                $user = $employee->user;
                $now = now();
                $temporaryAssignment = $user
                    ? $user->temporaryAssignments
                        ->where('is_active', true)
                        ->sortByDesc('to_date')
                        ->first(function ($assignment) use ($now) {
                            return $assignment->from_date
                                && $assignment->to_date
                                && ($now->between($assignment->from_date, $assignment->to_date) || $assignment->from_date->isFuture());
                        })
                    : null;

                $status = 'None';
                if ($temporaryAssignment) {
                    $status = $temporaryAssignment->from_date && $temporaryAssignment->to_date && $now->between($temporaryAssignment->from_date, $temporaryAssignment->to_date)
                        ? 'Active'
                        : 'Scheduled';
                }

                $baseRole = $temporaryAssignment
                    ? ($roleLabels[(int) $temporaryAssignment->original_role] ?? 'N/A')
                    : ($user ? ($roleLabels[(int) $user->role] ?? 'N/A') : 'N/A');

                $temporaryRole = $temporaryAssignment
                    ? ($roleLabels[(int) $temporaryAssignment->temporary_role] ?? 'Role')
                    : '—';

                fputcsv($file, [
                    $employee->id,
                    $employee->employee_code,
                    $employee->full_name,
                    $employee->email ?? '',
                    $employee->department?->name ?? 'N/A',
                    $employee->position?->title ?? 'N/A',
                    optional($temporaryAssignment?->from_date)->format('Y-m-d') ?? '',
                    optional($temporaryAssignment?->to_date)->format('Y-m-d') ?? '',
                    $status,
                    $baseRole,
                    $temporaryRole,
                    $temporaryAssignment?->grantedBy?->display_name ?? $temporaryAssignment?->grantedBy?->name ?? '—',
                ]);
            }

            fclose($file);
        }, 200, $responseHeaders);
    }

    /**
     * Display the temporary access details for a specific employee.
     */
    public function showTemporaryAccess(Employee $employee): \Illuminate\View\View
    {
        abort_if(auth()->user()->role !== 4, 403, 'Unauthorized access to temporary access details.');

        $employee->load([
            'department',
            'position',
            'user.temporaryAssignments' => function ($query) {
                $query->orderBy('created_at', 'desc')->with('grantedBy');
            }
        ]);

        return view('employees.temporary-access-show', compact('employee'));
    }

    /**
     * Build the base employee query for temporary access management.
     */
    private function temporaryAccessEmployeesQuery()
    {
        return Employee::whereDoesntHave('user', function ($query) {
            $query->whereIn('role', [2, 4]);
        });
    }

    /**
     * Normalize temp access filters from the request.
     */
    private function temporaryAccessFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->string('q')),
            'from_date' => trim((string) $request->string('from_date')),
            'to_date' => trim((string) $request->string('to_date')),
            'position_id' => trim((string) $request->string('position_id')),
            'department_id' => trim((string) $request->string('department_id')),
        ];
    }

    /**
     * Determine whether an employee matches the current temporary access filters.
     */
    private function matchesTemporaryAccessFilters(Employee $employee, array $filters): bool
    {
        $user = $employee->user;
        $temporaryAssignment = $this->currentTemporaryAssignment($user?->temporaryAssignments);

        if ($filters['q'] !== '') {
            $needle = mb_strtolower($filters['q']);
            $searchable = mb_strtolower(implode(' ', array_filter([
                (string) $employee->full_name,
                (string) $employee->employee_code,
                (string) ($employee->email ?? ''),
                (string) ($employee->position->title ?? ''),
                (string) ($employee->department->name ?? ''),
            ])));

            if (!str_contains($searchable, $needle)) {
                return false;
            }
        }

        if ($filters['position_id'] !== '' && (string) $employee->position_id !== $filters['position_id']) {
            return false;
        }

        if ($filters['department_id'] !== '' && (string) $employee->department_id !== $filters['department_id']) {
            return false;
        }

        $filterFrom = $filters['from_date'] !== '' ? \Carbon\Carbon::parse($filters['from_date'])->startOfDay() : null;
        $filterTo = $filters['to_date'] !== '' ? \Carbon\Carbon::parse($filters['to_date'])->endOfDay() : null;

        if ($filterFrom || $filterTo) {
            if (!$temporaryAssignment || !$temporaryAssignment->from_date || !$temporaryAssignment->to_date) {
                return false;
            }

            if ($filterFrom && $temporaryAssignment->to_date->lt($filterFrom)) {
                return false;
            }

            if ($filterTo && $temporaryAssignment->from_date->gt($filterTo)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find the active or scheduled temporary assignment that should be displayed.
     */
    private function currentTemporaryAssignment($temporaryAssignments)
    {
        if (!$temporaryAssignments) {
            return null;
        }

        $now = now();

        return $temporaryAssignments
            ->where('is_active', true)
            ->sortByDesc('to_date')
            ->first(function ($assignment) use ($now) {
                return $assignment->from_date
                    && $assignment->to_date
                    && ($now->between($assignment->from_date, $assignment->to_date) || $assignment->from_date->isFuture());
            });
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
