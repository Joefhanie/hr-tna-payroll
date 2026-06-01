<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\CompanyDocument;
use App\Models\EmployeeDocument;
use App\Models\OnboardingAssignment;
use App\Models\OnboardingTask;
use App\Services\OnboardingAssignmentService;
use App\Services\NotificationService;
use App\Support\UploadFilename;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OnboardingController extends Controller
{
    private bool $latestContractDocumentResolved = false;
    private ?CompanyDocument $latestContractDocumentCache = null;

    public function __construct(
        private readonly OnboardingAssignmentService $onboardingAssignmentService,
        private readonly NotificationService $notificationService
    )
    {
    }

    public function index(Request $request): View|StreamedResponse
    {
        $user = Auth::user();
        $role = (int) ($user?->role ?? 0);
        $isHr = $role === 4;
        $isSupervisor = $role === 2;
        $canManageTasks = $isHr || $isSupervisor;
        $isEmployeeView = !$canManageTasks;
        $filters = [
            'q' => trim((string) $request->string('q')),
            'employment_type' => (string) $request->string('employment_type'),
            'employee_status' => (string) $request->string('employee_status'),
            'department' => (string) $request->string('department'),
            'onboarding_status' => (string) $request->string('onboarding_status'),
        ];

        if (!$this->hasOnboardingTables()) {
            return view('onboarding', [
                'employees' => collect(),
                'selectedEmployee' => null,
                'isEmployeeView' => $isEmployeeView,
                'canAssignOnboarding' => false,
                'canManageTasks' => false,
                'filters' => $filters,
                'filterOptions' => $this->filterOptions(),
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
                'filters' => $filters,
                'filterOptions' => $this->filterOptions(),
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
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $search = $filters['q'];
                $query->where(function ($inner) use ($search) {
                    $inner->where('employee_code', 'like', '%' . $search . '%')
                        ->orWhere('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $search . '%'])
                        ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            })
            ->when($filters['employment_type'] !== '', function ($query) use ($filters) {
                $query->where('employment_type', (int) $filters['employment_type']);
            })
            ->when($filters['employee_status'] !== '' && is_numeric($filters['employee_status']), function ($query) use ($filters) {
                $query->where('status', (int) $filters['employee_status']);
            })
            ->when($filters['department'] !== '', function ($query) use ($filters) {
                $query->where('department_id', (int) $filters['department']);
            })
            ->orderByRaw(
                'CASE WHEN YEAR(hire_date) = ? AND MONTH(hire_date) = ? THEN 0 ELSE 1 END',
                [$now->year, $now->month]
            )
            ->orderByDesc('hire_date')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee) => $this->buildEmployeeCard($employee, true))
            ->when($filters['onboarding_status'] !== '', function ($employees) use ($filters) {
                return $employees->where('status_key', $filters['onboarding_status'])->values();
            })
            ->sortBy([
                fn (array $employee) => $employee['is_priority_hire'] ? 0 : 1,
                fn (array $employee) => -1 * ($employee['hire_date_sort'] ?? 0),
                fn (array $employee) => strtolower($employee['name']),
            ])
            ->values();

        $selectedEmployee = $employees->firstWhere('id', $selectedEmployeeId) ?? $employees->first();

        if ($request->boolean('export')) {
            return $this->exportOnboardingCsv($employees);
        }

        return view('onboarding', [
            'employees' => $employees,
            'selectedEmployee' => $selectedEmployee,
            'isEmployeeView' => false,
            'canAssignOnboarding' => $isHr,
            'canManageTasks' => true,
            'canCreateTasks' => $isHr,
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
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
            'company_document_ids' => ['nullable', 'array'],
            'company_document_ids.*' => ['integer', Rule::exists('company_documents', 'id')],
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
                'company_document_ids' => in_array($actionType, [OnboardingTask::ACTION_DOCUMENT_UPLOAD, OnboardingTask::ACTION_ACKNOWLEDGEMENT], true)
                    ? ($validated['company_document_ids'] ?? null)
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
            'company_document_ids' => in_array($actionType, [OnboardingTask::ACTION_DOCUMENT_UPLOAD, OnboardingTask::ACTION_ACKNOWLEDGEMENT], true)
                ? ($validated['company_document_ids'] ?? null)
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
                'document_file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
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

        $currentUser = Auth::user();
        if ($currentUser) {
            $this->notificationService->notifyOnboardingTaskCompleted($task, $currentUser);
        }

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

        $currentUser = Auth::user();
        if ($currentUser) {
            $this->notificationService->notifyOnboardingTaskCompleted($task->fresh(), $currentUser);
        }

        return redirect()
            ->route('onboarding', ['employee' => $task->assignment->employee_id])
            ->with('success', 'Onboarding task marked as done.');
    }

    public function downloadCompanyDocument(CompanyDocument $companyDocument): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($companyDocument->file_path), 404);

        return Storage::disk('public')->download($companyDocument->file_path, $companyDocument->file_name);
    }

    private function exportOnboardingCsv($employees): StreamedResponse
    {
        $filename = 'onboarding_export_' . now()->format('Ymd_His') . '.csv';

        $responseHeaders = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($employees) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Employee Code',
                'Employee Name',
                'Onboarding Type',
                'Status',
                'Progress',
                'Task Count',
                'Completed Tasks',
                'Pending Tasks',
                'Hire Date',
            ]);

            foreach ($employees as $employee) {
                $tasks = collect($employee['tasks'] ?? []);
                $taskCount = $tasks->count();
                $completedTasks = $tasks->where('completed', true)->count();

                fputcsv($file, [
                    $employee['employee_code'] ?? '',
                    $employee['name'] ?? '',
                    $employee['type'] ?? '',
                    $employee['status'] ?? '',
                    $employee['progress'] ?? 0,
                    $taskCount,
                    $completedTasks,
                    max(0, $taskCount - $completedTasks),
                    $employee['hire_date'] ?? '',
                ]);
            }

            fclose($file);
        }, 200, $responseHeaders);
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

        $companyDocuments = CompanyDocument::query()->orderBy('title')->get();

        $visibleTasks = null;

        if ($includeAllTasks) {
            $currentUser = Auth::user();
            $currentUserRole = (int) ($currentUser?->role ?? 0);
            $currentUserEmployeeId = $currentUser?->employee?->id ?? null;

            if ($currentUserRole === 2) {
                // Supervisor: show full task list only for their direct reports.
                // Otherwise show only tasks assigned to supervisors.
                $isDirectReport = $currentUserEmployeeId !== null && (int) ($employee->manager_id ?? 0) === (int) $currentUserEmployeeId;

                if ($isDirectReport) {
                    $visibleTasks = $allTasks->sortBy('sequence')->values();
                } else {
                    $visibleTasks = $allTasks
                        ->where('assigned_role', OnboardingTask::ASSIGNED_ROLE_SUPERVISOR)
                        ->sortBy('sequence')
                        ->values();
                }
            } else {
                // HR or other roles with full view
                $visibleTasks = $allTasks->sortBy('sequence')->values();
            }
        } else {
            $visibleTasks = $allTasks
                ->where('assigned_role', OnboardingTask::ASSIGNED_ROLE_EMPLOYEE)
                ->sortBy([
                    fn (OnboardingTask $task) => $task->completed_at !== null ? 1 : 0,
                    fn (OnboardingTask $task) => $task->sequence,
                ])
                ->values();
        }

        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'name' => $employee->full_name,
            'type' => $this->onboardingTypeForEmployee($employee),
            'status' => $status,
            'status_key' => $this->onboardingStatusKey($status),
            'progress' => $progress,
            'hire_date' => $employee->hire_date?->format('M d, Y') ?? 'N/A',
            'hire_date_sort' => $employee->hire_date?->timestamp ?? 0,
            'employment_type' => (int) ($employee->employment_type ?? 0),
            'employment_type_label' => $this->employmentTypeLabel((int) ($employee->employment_type ?? 0)),
            'employee_status' => (int) ($employee->status ?? 0),
            'employee_status_label' => $this->employeeStatusLabel((int) ($employee->status ?? 0)),
            'is_priority_hire' => $employee->hire_date !== null
                && (int) $employee->hire_date->year === (int) $now->year
                && (int) $employee->hire_date->month === (int) $now->month,
            'has_assignment' => $assignment !== null && $totalTasks > 0,
            'category_options' => $this->categoryOptions(),
            'document_type_options' => $this->documentTypeOptions(),
            'company_document_options' => $companyDocuments->map(fn (CompanyDocument $document) => [
                'value' => $document->id,
                'label' => trim($document->title ? $document->title . ' — ' . $document->file_name : $document->file_name),
            ])->values(),
            'task_owner_options' => $this->taskOwnerOptions(),
            'employee_action_options' => $this->employeeActionOptions(),
            'tasks' => $visibleTasks->map(function (OnboardingTask $task) {
                $contractDocument = $this->shouldAttachContractDocument($task)
                    ? $this->latestContractDocument()
                    : null;

                $attachedDocuments = collect($task->company_document_ids ?? [])
                    ->filter(fn ($id) => is_numeric($id))
                    ->map(fn ($id) => (int) $id)
                    ->pipe(function ($ids) {
                        if ($ids->isEmpty()) {
                            return collect();
                        }

                        return CompanyDocument::query()
                            ->whereIn('id', $ids)
                            ->orderBy('title')
                            ->get();
                    });

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
                    'company_document_ids' => $task->company_document_ids,
                    'attached_documents' => $attachedDocuments->map(fn (CompanyDocument $document) => [
                        'id' => $document->id,
                        'file_name' => $document->file_name,
                        'download_url' => route('onboarding.company-documents.download', $document),
                    ])->values(),
                    'submission_notes' => $task->submission_notes,
                    'submission_file_name' => $task->submission_file_name,
                    'submitted_at' => $task->submitted_at?->format('M d, Y h:i A'),
                    'completed_at' => $task->completed_at?->format('M d, Y h:i A'),
                    'completed' => $task->completed_at !== null,
                    'company_contract_name' => $contractDocument?->file_name,
                    'company_contract_download_url' => $contractDocument
                        ? route('onboarding.company-documents.download', $contractDocument)
                        : null,
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

    private function onboardingStatusKey(string $status): string
    {
        return match ($status) {
            'Completed' => 'completed',
            'In Progress' => 'in_progress',
            default => 'not_assigned',
        };
    }

    private function employmentTypeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Full-time',
            2 => 'Part-time',
            3 => 'Contractual',
            4 => 'Intern',
            default => 'Unknown',
        };
    }

    private function employeeStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Regular',
            2 => 'Probationary',
            3 => 'On Leave',
            4 => 'Resigned',
            5 => 'Terminated',
            default => 'Unknown',
        };
    }

    private function filterOptions(): array
    {
        return [
            'employment_types' => [
                ['value' => '1', 'label' => 'Full-time'],
                ['value' => '2', 'label' => 'Part-time'],
                ['value' => '3', 'label' => 'Contractual'],
                ['value' => '4', 'label' => 'Intern'],
            ],
            'employee_statuses' => [
                ['value' => '1', 'label' => 'Regular'],
                ['value' => '2', 'label' => 'Probationary'],
                ['value' => '3', 'label' => 'On Leave'],
                ['value' => '4', 'label' => 'Resigned'],
                ['value' => '5', 'label' => 'Terminated'],
            ],
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Department $department) => [
                    'value' => (string) $department->id,
                    'label' => $department->name,
                ])
                ->values()
                ->all(),
            'onboarding_statuses' => [
                ['value' => 'not_assigned', 'label' => 'Not Yet Assigned'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
            ],
        ];
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

        if ($role === 2) {
            // Supervisors may complete tasks explicitly assigned to supervisors
            if ($task->assigned_role === OnboardingTask::ASSIGNED_ROLE_SUPERVISOR) {
                return;
            }

            // Supervisors may also complete employee-assigned tasks for their direct reports
            $currentUserEmployeeId = Auth::user()?->employee?->id ?? null;

            // Resolve assigned employee robustly (assignment relation may not be eager-loaded)
            $assignedEmployee = null;
            $assignment = $task->assignment()->with('employee')->first();
            if ($assignment && $assignment->employee) {
                $assignedEmployee = $assignment->employee;
            }

            if ($task->assigned_role === OnboardingTask::ASSIGNED_ROLE_EMPLOYEE && $assignedEmployee && $currentUserEmployeeId !== null) {
                if ((int) $assignedEmployee->manager_id === (int) $currentUserEmployeeId) {
                    return;
                }
            }
        }

        abort(403);
    }

    private function storeTaskDocument(Employee $employee, OnboardingTask $task, array $validated): void
    {
        $file = $validated['document_file'];
        $folderName = 'employee-documents/' . ($employee->employee_code ?: $employee->id);
        $storedFileName = UploadFilename::build($file, null, $folderName);
        $storedPath = $file->storeAs($folderName, $storedFileName, 'public');

        $attributes = [
            'employee_id' => $employee->id,
            'file_name' => $storedFileName,
            'expiry_date' => $validated['expiry_date'] ?? null,
        ];

        if (Schema::hasColumn('employee_documents', 'display_name')) {
            $attributes['display_name'] = $task->title;
        }

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
            $attributes['file_extension'] = strtolower((string) $file->getClientOriginalExtension());
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
            'submission_file_name' => $storedFileName,
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
            ['value' => OnboardingTask::DOCUMENT_TYPE_EMPLOYMENT_CONTRACT, 'label' => OnboardingTask::DOCUMENT_TYPE_EMPLOYMENT_CONTRACT],
            ['value' => 'Resume', 'label' => 'Resume'],
            ['value' => 'Medical Certificate', 'label' => 'Medical Certificate'],
            ['value' => 'NDA', 'label' => 'NDA'],
            ['value' => 'Tax Form', 'label' => 'Tax Form'],
        ];
    }

    private function latestContractDocument(): ?CompanyDocument
    {
        if ($this->latestContractDocumentResolved) {
            return $this->latestContractDocumentCache;
        }

        $this->latestContractDocumentResolved = true;
        $this->latestContractDocumentCache = CompanyDocument::query()
            ->where('category', CompanyDocument::CATEGORY_CONTRACT)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->first();

        return $this->latestContractDocumentCache;
    }

    private function shouldAttachContractDocument(OnboardingTask $task): bool
    {
        return ($task->action_type ?? OnboardingTask::ACTION_CHECKLIST) === OnboardingTask::ACTION_DOCUMENT_UPLOAD
            && strcasecmp((string) $task->document_type, OnboardingTask::DOCUMENT_TYPE_EMPLOYMENT_CONTRACT) === 0;
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
