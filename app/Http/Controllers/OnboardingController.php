<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\OnboardingAssignment;
use App\Models\OnboardingTask;
use App\Services\OnboardingAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    public function __construct(private readonly OnboardingAssignmentService $onboardingAssignmentService)
    {
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $role = (int) ($user?->role ?? 0);
        $isHr = $role === 4;
        $isSupervisor = $role === 2;
        $canManageTasks = $isHr || $isSupervisor;
        $isEmployeeView = !$canManageTasks;

        if (!$this->hasOnboardingTables()) {
            return view('onboarding', [
                'employees' => collect(),
                'selectedEmployee' => null,
                'isEmployeeView' => $isEmployeeView,
                'canAssignOnboarding' => false,
                'canManageTasks' => false,
                'warningMessage' => 'Onboarding is not available until the latest migration is run.',
            ]);
        }

        if ($isEmployeeView) {
            $employee = $user?->employee;
            $selectedEmployee = $employee
                ? $this->buildEmployeeCard($this->loadEmployee($employee->id), false)
                : null;

            return view('onboarding', [
                'employees' => collect(),
                'selectedEmployee' => $selectedEmployee,
                'isEmployeeView' => true,
                'canAssignOnboarding' => false,
                'canManageTasks' => false,
                'canCreateTasks' => false,
                'warningMessage' => null,
            ]);
        }

        $now = Carbon::now();
        $selectedEmployeeId = (int) $request->integer('employee');

        $employees = Employee::query()
            ->with([
                'department',
                'position',
                'user',
                'onboardingAssignment.tasks',
            ])
            ->whereNotNull('hire_date')
            ->whereNotIn('status', [4, 5])
            ->orderByRaw(
                'CASE WHEN YEAR(hire_date) = ? AND MONTH(hire_date) = ? THEN 0 ELSE 1 END',
                [$now->year, $now->month]
            )
            ->orderByDesc('hire_date')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee) => $this->buildEmployeeCard($employee, true))
            ->sortBy([
                fn (array $employee) => $employee['is_priority_hire'] ? 0 : 1,
                fn (array $employee) => -1 * ($employee['hire_date_sort'] ?? 0),
                fn (array $employee) => strtolower($employee['name']),
            ])
            ->values();

        $selectedEmployee = $employees->firstWhere('id', $selectedEmployeeId) ?? $employees->first();

        return view('onboarding', [
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'isEmployeeView' => false,
            'canAssignOnboarding' => $isHr,
            'canManageTasks' => true,
            'canCreateTasks' => $isHr,
            'warningMessage' => null,
        ]);
    }

    public function start(Employee $employee): RedirectResponse
    {
        abort_unless((int) (Auth::user()?->role ?? 0) === 4, 403);

        if (!$this->hasOnboardingTables()) {
            return redirect()
                ->route('onboarding', ['employee' => $employee->id])
                ->with('error', 'Onboarding is not available until the latest migration is run.');
        }

        $this->onboardingAssignmentService->ensureEmployeeIsOnboarded($employee, Auth::id());

        return redirect()
            ->route('onboarding', ['employee' => $employee->id])
            ->with('success', 'Onboarding started successfully.');
    }

    public function storeTask(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless((int) (Auth::user()?->role ?? 0) === 4, 403);

        if (!$this->hasOnboardingTables()) {
            return redirect()
                ->route('onboarding', ['employee' => $employee->id])
                ->with('error', 'Onboarding is not available until the latest migration is run.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:80'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'assigned_role' => ['required', Rule::in($this->assignableRoles())],
            'action_type' => ['nullable', Rule::in($this->employeeActionTypes())],
            'document_type' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($employee, $validated) {
            $assignment = OnboardingAssignment::firstOrCreate(
                ['employee_id' => $employee->id],
                [
                    'assigned_by' => Auth::id(),
                    'status' => OnboardingAssignment::STATUS_IN_PROGRESS,
                    'started_at' => now(),
                ]
            );

            $assignedRole = $validated['assigned_role'];
            $actionType = $assignedRole === OnboardingTask::ASSIGNED_ROLE_EMPLOYEE
                ? ($validated['action_type'] ?? OnboardingTask::ACTION_CHECKLIST)
                : OnboardingTask::ACTION_CHECKLIST;

            $nextSequence = ((int) $assignment->tasks()->max('sequence')) + 1;

            $assignment->tasks()->create([
                'title' => $validated['title'],
                'category' => $validated['category'],
                'instructions' => $validated['instructions'] ?? null,
                'assigned_role' => $assignedRole,
                'action_type' => $actionType,
                'document_type' => $actionType === OnboardingTask::ACTION_DOCUMENT_UPLOAD
                    ? ($validated['document_type'] ?? $validated['category'])
                    : null,
                'sequence' => $nextSequence,
            ]);
        });

        return redirect()
            ->route('onboarding', ['employee' => $employee->id])
            ->with('success', 'Onboarding task created successfully.');
    }

    public function updateTask(Request $request, OnboardingTask $task): RedirectResponse
    {
        abort_unless((int) (Auth::user()?->role ?? 0) === 4, 403);

        if (!$this->hasOnboardingTables()) {
            return redirect()
                ->route('onboarding', ['employee' => $task->assignment->employee_id])
                ->with('error', 'Onboarding is not available until the latest migration is run.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:80'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'assigned_role' => ['required', Rule::in($this->assignableRoles())],
            'action_type' => ['nullable', Rule::in($this->employeeActionTypes())],
            'document_type' => ['nullable', 'string', 'max:100'],
        ]);

        $assignedRole = $validated['assigned_role'];
        $actionType = $assignedRole === OnboardingTask::ASSIGNED_ROLE_EMPLOYEE
            ? ($validated['action_type'] ?? OnboardingTask::ACTION_CHECKLIST)
            : OnboardingTask::ACTION_CHECKLIST;

        $task->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'instructions' => $validated['instructions'] ?? null,
            'assigned_role' => $assignedRole,
            'action_type' => $actionType,
            'document_type' => $actionType === OnboardingTask::ACTION_DOCUMENT_UPLOAD
                ? ($validated['document_type'] ?? $validated['category'])
                : null,
        ]);

        return redirect()
            ->route('onboarding', ['employee' => $task->assignment->employee_id])
            ->with('success', 'Onboarding task updated successfully.');
    }

    public function destroyTask(OnboardingTask $task): RedirectResponse
    {
        abort_unless((int) (Auth::user()?->role ?? 0) === 4, 403);

        $employeeId = $task->assignment->employee_id;

        $task->delete();
        $this->syncAssignmentStatus($task->assignment()->with('tasks')->first());

        return redirect()
            ->route('onboarding', ['employee' => $employeeId])
            ->with('success', 'Onboarding task deleted successfully.');
    }

    public function submitEmployeeTask(Request $request, OnboardingTask $task): RedirectResponse
    {
        $employee = $this->employeeForCurrentUser();
        abort_unless($employee && (int) $task->assignment?->employee_id === (int) $employee->id, 403);
        abort_unless($task->isEmployeeTask(), 403);

        if ($task->completed_at !== null) {
            return redirect()
                ->route('onboarding')
                ->with('success', 'This onboarding task is already completed.');
        }

        if ($task->action_type === OnboardingTask::ACTION_DOCUMENT_UPLOAD) {
            $validated = $request->validate([
                'document_file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
                'submission_notes' => ['nullable', 'string', 'max:2000'],
                'expiry_date' => ['nullable', 'date'],
            ]);

            $this->storeTaskDocument($employee, $task, $validated);
        } elseif ($task->action_type === OnboardingTask::ACTION_ACKNOWLEDGEMENT) {
            $validated = $request->validate([
                'acknowledged' => ['accepted'],
                'submission_notes' => ['nullable', 'string', 'max:2000'],
            ]);

            $task->update([
                'submission_notes' => $validated['submission_notes'] ?? 'Acknowledged by employee.',
                'submitted_at' => now(),
                'completed_at' => now(),
                'completed_by' => Auth::id(),
            ]);
        } else {
            $validated = $request->validate([
                'submission_notes' => ['nullable', 'string', 'max:2000'],
            ]);

            $task->update([
                'submission_notes' => $validated['submission_notes'] ?? null,
                'submitted_at' => now(),
                'completed_at' => now(),
                'completed_by' => Auth::id(),
            ]);
        }

        $this->syncAssignmentStatus($task->assignment);

        return redirect()
            ->route('onboarding')
            ->with('success', 'Onboarding action submitted successfully.');
    }

    public function completeTask(OnboardingTask $task): RedirectResponse
    {
        $role = (int) (Auth::user()?->role ?? 0);
        abort_unless(in_array($role, [2, 4], true), 403);

        if (!$this->hasOnboardingTables()) {
            return redirect()
                ->route('onboarding')
                ->with('error', 'Onboarding is not available until the latest migration is run.');
        }

        $this->authorizeStaffTaskCompletion($task, $role);

        DB::transaction(function () use ($task) {
            if ($task->completed_at === null) {
                $task->update([
                    'completed_at' => now(),
                    'completed_by' => Auth::id(),
                ]);
            }

            $this->syncAssignmentStatus($task->assignment()->with('tasks')->first());
        });

        return redirect()
            ->route('onboarding', ['employee' => $task->assignment->employee_id])
            ->with('success', 'Onboarding task marked as done.');
    }

    private function buildEmployeeCard(Employee $employee, bool $includeAllTasks): array
    {
        $now = Carbon::now();
        $assignment = $employee->onboardingAssignment;
        $allTasks = collect($assignment?->tasks ?? []);
        $totalTasks = $allTasks->count();
        $completedTasks = $allTasks->whereNotNull('completed_at')->count();

        if (!$assignment || $totalTasks === 0) {
            $status = 'Not Yet Assigned';
            $progress = 0;
        } elseif ($completedTasks >= $totalTasks) {
            $status = 'Completed';
            $progress = 100;
        } else {
            $status = 'In Progress';
            $progress = (int) round(($completedTasks / $totalTasks) * 100);
        }

        $visibleTasks = $includeAllTasks
            ? $allTasks->sortBy('sequence')->values()
            : $allTasks
                ->where('assigned_role', OnboardingTask::ASSIGNED_ROLE_EMPLOYEE)
                ->sortBy([
                    fn (OnboardingTask $task) => $task->completed_at !== null ? 1 : 0,
                    fn (OnboardingTask $task) => $task->sequence,
                ])
                ->values();

        return [
            'id' => $employee->id,
            'name' => $employee->full_name,
            'type' => $this->onboardingTypeForEmployee($employee),
            'status' => $status,
            'progress' => $progress,
            'hire_date' => $employee->hire_date?->format('M d, Y') ?? 'N/A',
            'hire_date_sort' => $employee->hire_date?->timestamp ?? 0,
            'is_priority_hire' => $employee->hire_date !== null
                && (int) $employee->hire_date->year === (int) $now->year
                && (int) $employee->hire_date->month === (int) $now->month,
            'has_assignment' => $assignment !== null && $totalTasks > 0,
            'category_options' => $this->categoryOptions(),
            'document_type_options' => $this->documentTypeOptions(),
            'task_owner_options' => $this->taskOwnerOptions(),
            'employee_action_options' => $this->employeeActionOptions(),
            'tasks' => $visibleTasks->map(function (OnboardingTask $task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'category' => $task->category,
                    'instructions' => $task->instructions,
                    'assigned_role' => $task->assigned_role,
                    'assigned_role_label' => $this->assignedRoleLabel($task->assigned_role),
                    'action_type' => $task->action_type ?? OnboardingTask::ACTION_CHECKLIST,
                    'action_type_label' => $this->actionTypeLabel($task->action_type ?? OnboardingTask::ACTION_CHECKLIST),
                    'document_type' => $task->document_type,
                    'submission_notes' => $task->submission_notes,
                    'submission_file_name' => $task->submission_file_name,
                    'submitted_at' => $task->submitted_at?->format('M d, Y h:i A'),
                    'completed_at' => $task->completed_at?->format('M d, Y h:i A'),
                    'completed' => $task->completed_at !== null,
                ];
            })->values(),
        ];
    }

    private function onboardingTypeForEmployee(Employee $employee): string
    {
        $departmentName = trim((string) ($employee->department?->name ?? ''));

        if ($departmentName !== '') {
            return $departmentName . ' Onboarding';
        }

        return 'General Onboarding';
    }

    private function hasOnboardingTables(): bool
    {
        return $this->onboardingAssignmentService->hasOnboardingTables();
    }

    private function loadEmployee(int $employeeId): ?Employee
    {
        return Employee::query()
            ->with([
                'department',
                'position',
                'user',
                'onboardingAssignment.tasks',
            ])
            ->find($employeeId);
    }

    private function syncAssignmentStatus(?OnboardingAssignment $assignment): void
    {
        if (!$assignment) {
            return;
        }

        $assignment->loadMissing('tasks');
        $isCompleted = $assignment->tasks->isNotEmpty()
            && $assignment->tasks->every(fn (OnboardingTask $item) => $item->completed_at !== null);

        $assignment->update([
            'status' => $isCompleted ? OnboardingAssignment::STATUS_COMPLETED : OnboardingAssignment::STATUS_IN_PROGRESS,
            'completed_at' => $isCompleted ? now() : null,
        ]);
    }

    private function authorizeStaffTaskCompletion(OnboardingTask $task, int $role): void
    {
        if ($role === 4) {
            return;
        }

        if ($role === 2 && $task->assigned_role === OnboardingTask::ASSIGNED_ROLE_SUPERVISOR) {
            return;
        }

        abort(403);
    }

    private function storeTaskDocument(Employee $employee, OnboardingTask $task, array $validated): void
    {
        $file = $validated['document_file'];
        $storedPath = $file->store('onboarding-documents/' . $employee->id, 'public');
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $attributes = [
            'employee_id' => $employee->id,
            'file_name' => $file->getClientOriginalName(),
            'expiry_date' => $validated['expiry_date'] ?? null,
        ];

        if (Schema::hasColumn('employee_documents', 'document_type')) {
            $attributes['document_type'] = $task->document_type ?: $task->category;
        }

        if (Schema::hasColumn('employee_documents', 'doc_type')) {
            $attributes['doc_type'] = $task->document_type ?: $task->category;
        }

        if (Schema::hasColumn('employee_documents', 'file_path')) {
            $attributes['file_path'] = $storedPath;
        }

        if (Schema::hasColumn('employee_documents', 'file_url')) {
            $attributes['file_url'] = $storedPath;
        }

        if (Schema::hasColumn('employee_documents', 'file_extension')) {
            $attributes['file_extension'] = $extension;
        }

        if (Schema::hasColumn('employee_documents', 'file_size')) {
            $attributes['file_size'] = $file->getSize();
        }

        if (Schema::hasColumn('employee_documents', 'file_size_kb')) {
            $attributes['file_size_kb'] = round($file->getSize() / 1024, 2);
        }

        if (Schema::hasColumn('employee_documents', 'description')) {
            $attributes['description'] = $validated['submission_notes'] ?? ('Submitted from onboarding task: ' . $task->title);
        }

        if (Schema::hasColumn('employee_documents', 'uploaded_by')) {
            $attributes['uploaded_by'] = Auth::id();
        }

        if (Schema::hasColumn('employee_documents', 'uploaded_at')) {
            $attributes['uploaded_at'] = now();
        }

        if (Schema::hasColumn('employee_documents', 'created_at')) {
            $attributes['created_at'] = now();
        }

        if (Schema::hasColumn('employee_documents', 'updated_at')) {
            $attributes['updated_at'] = now();
        }

        EmployeeDocument::create($attributes);

        $task->update([
            'submission_notes' => $validated['submission_notes'] ?? null,
            'submission_file_name' => $file->getClientOriginalName(),
            'submission_file_path' => $storedPath,
            'submitted_at' => now(),
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ]);
    }

    private function employeeForCurrentUser(): ?Employee
    {
        return Auth::user()?->employee;
    }

    private function taskOwnerOptions(): array
    {
        return [
            ['value' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE, 'label' => 'Employee'],
            ['value' => OnboardingTask::ASSIGNED_ROLE_HR, 'label' => 'HR'],
            ['value' => OnboardingTask::ASSIGNED_ROLE_SUPERVISOR, 'label' => 'Supervisor'],
        ];
    }

    private function employeeActionOptions(): array
    {
        return [
            ['value' => OnboardingTask::ACTION_CHECKLIST, 'label' => 'Simple completion'],
            ['value' => OnboardingTask::ACTION_DOCUMENT_UPLOAD, 'label' => 'Upload document'],
            ['value' => OnboardingTask::ACTION_ACKNOWLEDGEMENT, 'label' => 'Acknowledge / accept'],
        ];
    }

    private function categoryOptions(): array
    {
        return [
            ['value' => 'Documents', 'label' => 'Documents'],
            ['value' => 'HR', 'label' => 'HR'],
            ['value' => 'IT Setup', 'label' => 'IT Setup'],
            ['value' => 'Training', 'label' => 'Training'],
            ['value' => 'Orientation', 'label' => 'Orientation'],
            ['value' => 'Equipment', 'label' => 'Equipment'],
            ['value' => 'Compliance', 'label' => 'Compliance'],
        ];
    }

    private function documentTypeOptions(): array
    {
        return [
            ['value' => 'Government IDs', 'label' => 'Government IDs'],
            ['value' => 'Picture', 'label' => 'Picture'],
            ['value' => 'Resume', 'label' => 'Resume'],
            ['value' => 'Employment Contract', 'label' => 'Employment Contract'],
            ['value' => 'Medical Certificate', 'label' => 'Medical Certificate'],
            ['value' => 'NDA', 'label' => 'NDA'],
            ['value' => 'Tax Form', 'label' => 'Tax Form'],
        ];
    }

    private function assignableRoles(): array
    {
        return array_column($this->taskOwnerOptions(), 'value');
    }

    private function employeeActionTypes(): array
    {
        return array_column($this->employeeActionOptions(), 'value');
    }

    private function assignedRoleLabel(string $assignedRole): string
    {
        return match ($assignedRole) {
            OnboardingTask::ASSIGNED_ROLE_EMPLOYEE => 'Employee',
            OnboardingTask::ASSIGNED_ROLE_HR => 'HR',
            OnboardingTask::ASSIGNED_ROLE_SUPERVISOR => 'Supervisor',
            default => ucfirst($assignedRole),
        };
    }

    private function actionTypeLabel(string $actionType): string
    {
        return match ($actionType) {
            OnboardingTask::ACTION_DOCUMENT_UPLOAD => 'Upload required',
            OnboardingTask::ACTION_ACKNOWLEDGEMENT => 'Acknowledgement',
            default => 'Checklist',
        };
    }
}
