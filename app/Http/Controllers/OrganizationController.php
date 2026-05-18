<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function departments(): View
    {
        $departments = Department::with(['parentDepartment', 'employees', 'positions'])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        $departmentRows = $this->flattenDepartmentsByHierarchy($departments);

        $positions = Position::with('department')
            ->orderBy('title')
            ->get();

        $departmentOptions = $this->departmentOptionsByHierarchy();

        return view('organization.departments', compact('departments', 'departmentRows', 'positions', 'departmentOptions'));
    }

    public function positions(): View
    {
        $positions = Position::with('department')
            ->orderBy('title')
            ->get();

        $departmentOptions = $this->departmentOptionsByHierarchy();

        return view('organization.positions', compact('positions', 'departmentOptions'));
    }

    public function users(): View
    {
        $users = User::with('employee')
            ->orderBy('name')
            ->get();

        $availableEmployees = $this->availableEmployees();

        return view('organization.users', compact('users', 'availableEmployees'));
    }

    public function editUser(User $user): View
    {
        $user->load('employee');

        $users = User::with('employee')->orderBy('name')->get();
        $availableEmployees = $this->availableEmployees($user->employee);

        return view('organization.users', [
            'users' => $users,
            'editingUser' => $user,
            'availableEmployees' => $availableEmployees,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $availableEmployees = $this->availableEmployees();
        $associateEmployee = $request->boolean('has_employee_record');
        $availableEmployeeIds = $availableEmployees->pluck('id')->all();

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username', 'alpha_dash'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'in:1,2,3,4'],
            'has_employee_record' => ['nullable', 'boolean'],
            'employee_id' => [
                Rule::requiredIf($associateEmployee && ! empty($availableEmployeeIds)),
                'nullable',
                Rule::in($availableEmployeeIds),
            ],
        ]);

        $user = User::create([
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'] ?? 4,
            'status' => 2,
            'employee_id' => $associateEmployee ? ($validated['employee_id'] ?? null) : null,
        ]);

        if (! $associateEmployee) {
            $request->session()->put('pending_employee_user_id', $user->id);

            return redirect()->route('employees.create')->with('success', 'User credentials saved. Continue by creating the employee profile.');
        }

        if ($associateEmployee && empty($validated['employee_id']) && $availableEmployees->isEmpty()) {
            $request->session()->put('pending_employee_user_id', $user->id);

            return redirect()->route('employees.create')->with('success', 'User credentials saved. Complete the employee profile to finish linking the account.');
        }

        return redirect()->route('organization.users.index')->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $user->load('employee');

        $availableEmployees = $this->availableEmployees($user->employee);
        $associateEmployee = $request->boolean('has_employee_record');
        $availableEmployeeIds = $availableEmployees->pluck('id')->all();

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:255', 'alpha_dash', 'unique:users,username,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'in:1,2,3,4'],
            'status' => ['nullable', 'integer'],
            'has_employee_record' => ['nullable', 'boolean'],
            'employee_id' => [
                Rule::requiredIf($associateEmployee && ! empty($availableEmployeeIds)),
                'nullable',
                Rule::in($availableEmployeeIds),
            ],
        ]);

        $user->fill([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? $user->role,
            'employee_id' => $associateEmployee ? ($validated['employee_id'] ?? null) : null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        if (! $associateEmployee) {
            $request->session()->put('pending_employee_user_id', $user->id);

            return redirect()->route('employees.create')->with('success', 'User updated successfully. Continue by creating the employee profile.');
        }

        if ($associateEmployee && empty($validated['employee_id']) && $availableEmployees->isEmpty()) {
            $request->session()->put('pending_employee_user_id', $user->id);

            return redirect()->route('employees.create')->with('success', 'User credentials saved. Complete the employee profile to finish linking the account.');
        }

        return redirect()->route('organization.users.index')->with('success', 'User updated successfully.');
    }

    private function availableEmployees(?Employee $currentEmployee = null)
    {
        $linkedEmployeeIds = User::query()
            ->whereNotNull('employee_id')
            ->pluck('employee_id')
            ->all();

        $employees = Employee::query()
            ->when(! empty($linkedEmployeeIds), function ($query) use ($linkedEmployeeIds) {
                $query->whereNotIn('id', $linkedEmployeeIds);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        if ($currentEmployee && ! $employees->contains('id', $currentEmployee->id)) {
            $employees = $employees->prepend($currentEmployee);
        }

        return $employees->values();
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_dept_id' => ['nullable', 'exists:departments,id'],
        ]);

        Department::create($validated);

        return redirect()->route('organization.departments.index')->with('success', 'Department created successfully.');
    }

    public function showDepartment(Department $department): View
    {
        return view('organization.departments-show', compact('department'));
    }

    public function editDepartment(Department $department): View
    {
        $departments = Department::where('id', '!=', $department->id)
            ->with('parentDepartment')
            ->orderBy('name')
            ->get();

        return view('organization.departments-edit', compact('department', 'departments'));
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_dept_id' => ['nullable', 'exists:departments,id'],
        ]);

        $department->update($validated);

        return redirect()->route('organization.departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('organization.departments.index')->with('success', 'Department deleted successfully.');
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'level' => ['nullable', 'string', 'max:60'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'gte:min_salary'],
        ]);

        Position::create($validated);

        return redirect()->route('organization.departments.index')->with('success', 'Position created successfully.');
    }

    public function showPosition(Position $position): View
    {
        return view('organization.positions-show', compact('position'));
    }

    public function editPosition(Position $position): View
    {
        $departmentOptions = $this->departmentOptionsByHierarchy();

        return view('organization.positions-edit', compact('position', 'departmentOptions'));
    }

    /**
     * Build a flattened department list ordered by hierarchy depth.
     *
        * @return array<int, array{id:int,name:string,depth:int,label:string}>
     */
    private function departmentOptionsByHierarchy(): array
    {
        $departments = Department::query()
            ->select(['id', 'name', 'parent_dept_id'])
            ->orderBy('name')
            ->get();

        $departmentRows = $this->flattenDepartmentsByHierarchy($departments);

        return array_map(static function (array $row): array {
            /** @var Department $department */
            $department = $row['department'];

            return [
                'id' => $department->id,
                'name' => $department->name,
                'depth' => $row['depth'],
                'label' => $row['path'],
            ];
        }, $departmentRows);
    }

    /**
     * Flatten departments in parent-to-child display order.
     *
     * @param Collection<int, Department> $departments
    * @return array<int, array{department: Department, depth: int, path: string}>
     */
    private function flattenDepartmentsByHierarchy(Collection $departments): array
    {
        $byParent = $departments->groupBy(function (Department $department) {
            return $department->parent_dept_id ?? 0;
        });

        $rows = [];
        $appendChildren = function (int $parentId, int $depth, string $parentPath = '') use (&$appendChildren, &$rows, $byParent): void {
            foreach ($byParent->get($parentId, collect()) as $department) {
                $currentPath = $parentPath === ''
                    ? $department->name
                    : $parentPath . ' / ' . $department->name;

                $rows[] = [
                    'department' => $department,
                    'depth' => $depth,
                    'path' => $currentPath,
                ];

                $appendChildren($department->id, $depth + 1, $currentPath);
            }
        };

        $appendChildren(0, 0, '');

        return $rows;
    }

    public function updatePosition(Request $request, Position $position): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'level' => ['nullable', 'string', 'max:60'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'gte:min_salary'],
        ]);

        $position->update($validated);

        return redirect()->route('organization.departments.index')->with('success', 'Position updated successfully.');
    }

    public function destroyPosition(Position $position): RedirectResponse
    {
        $position->delete();

        return redirect()->route('organization.departments.index')->with('success', 'Position deleted successfully.');
    }
}
