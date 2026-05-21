<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Leave;
use App\Models\Payslip;
use App\Models\ProfileUpdateRequest;
use App\Models\User;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SelfServiceController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService
    ) {
    }

    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user instanceof User && (int) ($user->role ?? 0) !== 4 && !empty($user->employee_id)) {
            return redirect()->route('self-service.profile', $user->employee_id);
        }

        $filters = [
            'q' => trim((string) $request->string('q')),
            'type' => trim((string) $request->string('type')),
            'status' => trim((string) $request->string('status')),
        ];

        $feed = Employee::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', [1, 2]);
            })
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Employee $employee) {
                $linkedUser = $employee->user;
                $role = (int) ($linkedUser ? $linkedUser->role : 0);

                return [
                    'code' => $employee->employee_code ?? 'EMP-' . str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT),
                    'id' => $employee->id,
                    'employee' => $employee->full_name,
                    'email' => $employee->email ?? ($linkedUser ? $linkedUser->email : 'N/A'),
                    'type' => $this->roleLabel($role),
                    'date' => optional($employee->hire_date)->format('Y-m-d') ?? 'N/A',
                    'status' => $this->employeeStatusLabel((int) ($employee->status ?? 0)),
                ];
            });

        $requests = $feed
            ->when($filters['q'] !== '', function (Collection $collection) use ($filters) {
                $needle = mb_strtolower($filters['q']);

                return $collection->filter(function (array $item) use ($needle) {
                    return str_contains(mb_strtolower($item['code']), $needle)
                        || str_contains(mb_strtolower($item['employee']), $needle)
                        || str_contains(mb_strtolower($item['email']), $needle)
                        || str_contains(mb_strtolower($item['type']), $needle);
                });
            })
            ->when($filters['type'] !== '', fn (Collection $collection) => $collection->where('type', $filters['type']))
            ->when($filters['status'] !== '', fn (Collection $collection) => $collection->where('status', $filters['status']))
            ->values();

        if ($request->boolean('export')) {
            return $this->export($requests);
        }

        return view('self-service', [
            'requests' => $requests,
            'filters' => $filters,
            'requestTypes' => $feed->pluck('type')->unique()->sort()->values(),
            'statuses' => $feed->pluck('status')->unique()->sort()->values(),
        ]);
    }

    public function profile(Employee $employee)
    {
        $employee->load([
            'department',
            'position',
            'manager',
            'user',
            'documents',
            'salaryRecords',
        ]);

        $leaveRequests = collect();
        $attendanceLogs = collect();
        $leaveTypes = $this->leaveTypeOptions();

        if ($employee->user) {
            $leaveRequests = Leave::query()
                ->where('employee_id', $employee->id)
                ->latest('created_at')
                ->get()
                ->map(function (Leave $leave) use ($leaveTypes) {
                    return [
                        'type' => $leaveTypes[(int) ($leave->leave_type_id ?? 0)] ?? 'Leave Request',
                        'start_date' => optional($leave->start_date)->format('M d, Y'),
                        'end_date' => optional($leave->end_date)->format('M d, Y'),
                        'reason' => $leave->reason,
                        'status' => $this->requestStatusLabel((int) $leave->status),
                        'created_at' => optional($leave->created_at)->format('M d, Y h:i A'),
                    ];
                });

            $attendanceLogs = Attendance::query()
                ->where('user_id', $employee->user->id)
                ->latest('attendance_date')
                ->limit(12)
                ->get()
                ->map(function (Attendance $attendance) {
                    return [
                        'date' => optional($attendance->attendance_date)->format('M d, Y'),
                        'check_in' => optional($attendance->check_in)->format('h:i A') ?? 'N/A',
                        'check_out' => optional($attendance->check_out)->format('h:i A') ?? 'N/A',
                        'status' => $this->attendanceStatusLabel((int) $attendance->status),
                        'notes' => $attendance->notes,
                    ];
                });
        }

        $profileUpdateRequests = collect();

        if (Schema::hasTable('profile_update_requests')) {
            $profileUpdateRequests = ProfileUpdateRequest::query()
                ->where('employee_id', $employee->id)
                ->latest('created_at')
                ->get()
                ->map(function (ProfileUpdateRequest $profileUpdateRequest) {
                    return [
                        'requested_fields' => collect($profileUpdateRequest->requested_changes ?? [])
                            ->keys()
                            ->map(fn ($field) => str_replace('_', ' ', ucfirst((string) $field)))
                            ->implode(', '),
                        'status' => $this->requestStatusLabel((int) $profileUpdateRequest->status),
                        'remarks' => $profileUpdateRequest->remarks,
                        'created_at' => optional($profileUpdateRequest->created_at)->format('M d, Y h:i A'),
                    ];
                });
        }

        $payslips = Payslip::query()
            ->with(['payRun', 'lineItems'])
            ->where('employee_id', $employee->id)
            ->latest('released_at')
            ->latest('created_at')
            ->limit(12)
            ->get()
            ->map(function (Payslip $payslip) use ($employee) {
                $salaryRecord = null;

                if ($payslip->payRun && $payslip->payRun->period_end) {
                    $payRunEnd = \Carbon\Carbon::parse($payslip->payRun->period_end)->toDateString();
                    $salaryRecord = $employee->salaryRecords
                        ->filter(function ($record) use ($payRunEnd) {
                            $effectiveDate = \Carbon\Carbon::parse($record->effective_date)->toDateString();
                            $endDate = $record->end_date ? \Carbon\Carbon::parse($record->end_date)->toDateString() : null;

                            return $effectiveDate <= $payRunEnd && (!$endDate || $endDate >= $payRunEnd);
                        })
                        ->sortByDesc('effective_date')
                        ->first();
                }

                $payFrequencyLabels = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];

                return [
                    'id' => $payslip->id,
                    'period' => $payslip->payRun
                        ? optional($payslip->payRun->period_start)->format('M d, Y') . ' - ' . optional($payslip->payRun->period_end)->format('M d, Y')
                        : 'N/A',
                    'pay_date' => $payslip->payRun && $payslip->payRun->pay_date
                        ? $payslip->payRun->pay_date->format('M d, Y')
                        : 'N/A',
                    'gross_pay' => number_format((float) $payslip->gross_pay, 2),
                    'total_deductions' => number_format((float) $payslip->total_deductions, 2),
                    'net_pay' => number_format((float) $payslip->net_pay, 2),
                    'gross_pay_value' => (float) $payslip->gross_pay,
                    'total_deductions_value' => (float) $payslip->total_deductions,
                    'net_pay_value' => (float) $payslip->net_pay,
                    'base_salary_value' => $salaryRecord ? (float) $salaryRecord->amount : 0,
                    'frequency_label' => $salaryRecord ? ($payFrequencyLabels[$salaryRecord->pay_frequency] ?? 'Unknown') : 'Unknown',
                    'line_items' => $payslip->lineItems
                        ->map(fn ($item) => [
                            'component_type' => (int) $item->component_type,
                            'description' => (string) $item->description,
                            'amount' => (float) $item->amount,
                            'is_taxable' => (bool) $item->is_taxable,
                        ])
                        ->values()
                        ->all(),
                    'status' => $this->payslipStatusLabel($payslip->status),
                ];
            });

        $documents = $employee->documents
            ->sortByDesc(function (EmployeeDocument $document) {
                return $document->uploaded_at ?? $document->created_at ?? $document->issued_date;
            })
            ->values()
            ->map(function (EmployeeDocument $document) {
                return [
                    'name' => $document->file_name,
                    'type' => $document->doc_type ?? $document->document_type ?? 'Document',
                    'date' => optional($document->uploaded_at ?? $document->created_at ?? $document->issued_date)->format('M d, Y'),
                    'file_path' => $document->file_url ?? $document->file_path ?? null,
                    'file_size' => $this->formatDocumentFileSize($document),
                ];
            });

        return view('self-service.profile', [
            'employee' => $employee,
            'canSubmitRequests' => $this->canSubmitForEmployee($employee),
            'leaveTypeOptions' => $leaveTypes,
            'leaveTypeChoices' => $this->leaveTypeChoices(),
            'leaveRequests' => $leaveRequests,
            'attendanceLogs' => $attendanceLogs,
            'profileUpdateRequests' => $profileUpdateRequests,
            'payslips' => $payslips,
            'documents' => $documents,
        ]);
    }

    public function storeLeaveRequest(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployeeSubmission($employee);

        if (!$employee->user) {
            throw ValidationException::withMessages([
                'leave' => 'This employee does not have a linked user account yet.',
            ]);
        }

        $validated = validator(
            [
                ...$request->all(),
                'employee_id' => $employee->id,
            ],
            LeaveRequestService::rules()
        )->validate();

        $this->leaveRequestService->submit(
            Auth::user(),
            $validated
        );

        return redirect()
            ->route('self-service.profile', $employee)
            ->with('success', 'Leave request submitted successfully.');
    }

    public function storeProfileUpdateRequest(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployeeSubmission($employee);

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!Schema::hasTable('profile_update_requests')) {
            throw ValidationException::withMessages([
                'profile_update' => 'Profile update requests are not available until the latest migration is run.',
            ]);
        }

        $requestableFields = [
            'first_name',
            'last_name',
            'middle_name',
            'email',
            'phone',
            'address_line1',
            'address_line2',
            'city',
            'province',
            'postal_code',
            'country',
        ];

        $changes = [];

        foreach ($requestableFields as $field) {
            $newValue = array_key_exists($field, $validated) ? trim((string) ($validated[$field] ?? '')) : '';
            $currentValue = trim((string) ($employee->{$field} ?? ''));

            if ($newValue !== '' && $newValue !== $currentValue) {
                $changes[$field] = [
                    'from' => $currentValue !== '' ? $currentValue : null,
                    'to' => $newValue,
                ];
            }
        }

        if ($changes === []) {
            throw ValidationException::withMessages([
                'profile_update' => 'Provide at least one changed value before submitting a profile update request.',
            ]);
        }

        ProfileUpdateRequest::create([
            'employee_id' => $employee->id,
            'requested_by' => Auth::id(),
            'requested_changes' => $changes,
            'notes' => $validated['notes'] ?? null,
            'status' => 1,
        ]);

        return redirect()
            ->route('self-service.profile', $employee)
            ->with('success', 'Profile update request submitted successfully.');
    }

    public function storeDocumentUpload(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployeeSubmission($employee);

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'document_file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'description' => ['nullable', 'string', 'max:2000'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $file = $validated['document_file'];
        $storedPath = $file->store('self-service-documents/' . $employee->id, 'public');
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $attributes = [
            'employee_id' => $employee->id,
            'file_name' => $file->getClientOriginalName(),
            'expiry_date' => $validated['expiry_date'] ?? null,
        ];

        if (Schema::hasColumn('employee_documents', 'document_type')) {
            $attributes['document_type'] = $validated['document_type'];
        }

        if (Schema::hasColumn('employee_documents', 'doc_type')) {
            $attributes['doc_type'] = $validated['document_type'];
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
            $attributes['description'] = $validated['description'] ?? null;
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

        return redirect()
            ->route('self-service.profile', $employee)
            ->with('success', 'Document uploaded successfully.');
    }

    private function buildRequestFeed(): Collection
    {
        $leaveTypes = $this->leaveTypeOptions();

        $leaveItems = Leave::query()
            ->with('employee')
            ->latest('created_at')
            ->get()
            ->map(function (Leave $leave) use ($leaveTypes) {
                $employee = $leave->employee;

                return [
                    'code' => 'LEAVE-' . str_pad((string) $leave->id, 4, '0', STR_PAD_LEFT),
                    'id' => $employee ? $employee->id : null,
                    'employee' => $employee ? $employee->full_name : 'Unknown Employee',
                    'email' => $employee ? $employee->email : 'N/A',
                    'type' => $leaveTypes[(int) ($leave->leave_type_id ?? 0)] ?? 'Leave Request',
                    'date' => optional($leave->created_at)->format('Y-m-d'),
                    'status' => $this->requestStatusLabel((int) $leave->status),
                ];
            });

        $profileUpdateItems = collect();

        if (Schema::hasTable('profile_update_requests')) {
            $profileUpdateItems = ProfileUpdateRequest::query()
                ->with('employee')
                ->latest('created_at')
                ->get()
                ->map(function (ProfileUpdateRequest $profileUpdateRequest) {
                    $employee = $profileUpdateRequest->employee;

                    return [
                        'code' => 'PROFILE-' . str_pad((string) $profileUpdateRequest->id, 4, '0', STR_PAD_LEFT),
                        'id' => $employee ? $employee->id : null,
                        'employee' => $employee ? $employee->full_name : 'Unknown Employee',
                        'email' => $employee ? $employee->email : 'N/A',
                        'type' => 'Profile Update',
                        'date' => optional($profileUpdateRequest->created_at)->format('Y-m-d'),
                        'status' => $this->requestStatusLabel((int) $profileUpdateRequest->status),
                    ];
                });
        }

        $documentQuery = EmployeeDocument::query()
            ->with('employee');

        if (Schema::hasColumn('employee_documents', 'uploaded_at')) {
            $documentQuery->latest('uploaded_at');
        }

        if (Schema::hasColumn('employee_documents', 'created_at')) {
            $documentQuery->latest('created_at');
        }

        $documentItems = $documentQuery
            ->latest('id')
            ->get()
            ->map(function (EmployeeDocument $document) {
                $employee = $document->employee;

                return [
                    'code' => 'DOC-' . str_pad((string) $document->id, 4, '0', STR_PAD_LEFT),
                    'id' => $employee ? $employee->id : null,
                    'employee' => $employee ? $employee->full_name : 'Unknown Employee',
                    'email' => $employee ? $employee->email : 'N/A',
                    'type' => 'Document Upload',
                    'date' => optional($document->uploaded_at ?? $document->created_at ?? $document->issued_date)->format('Y-m-d'),
                    'status' => 'Completed',
                ];
            });

        $payslipItems = Payslip::query()
            ->with('employee')
            ->whereNotNull('released_at')
            ->latest('released_at')
            ->get()
            ->map(function (Payslip $payslip) {
                $employee = $payslip->employee;

                return [
                    'code' => 'PAY-' . str_pad((string) $payslip->id, 4, '0', STR_PAD_LEFT),
                    'id' => $employee ? $employee->id : null,
                    'employee' => $employee ? $employee->full_name : 'Unknown Employee',
                    'email' => $employee ? $employee->email : 'N/A',
                    'type' => 'Payslip Release',
                    'date' => optional($payslip->released_at)->format('Y-m-d'),
                    'status' => $this->payslipStatusLabel($payslip->status),
                ];
            });

        return $leaveItems
            ->concat($profileUpdateItems)
            ->concat($documentItems)
            ->concat($payslipItems)
            ->filter(fn (array $item) => !empty($item['id']))
            ->sortByDesc('date')
            ->values();
    }

    private function export(Collection $requests): StreamedResponse
    {
        return response()->streamDownload(function () use ($requests) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Employee', 'Email', 'Request Type', 'Date', 'Status']);

            foreach ($requests as $request) {
                fputcsv($handle, [
                    $request['code'],
                    $request['employee'],
                    $request['email'],
                    $request['type'],
                    $request['date'],
                    $request['status'],
                ]);
            }

            fclose($handle);
        }, 'self-service-export.csv');
    }

    private function authorizeEmployeeSubmission(Employee $employee): void
    {
        abort_unless($this->canSubmitForEmployee($employee), 403);
    }

    private function canSubmitForEmployee(Employee $employee): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        if ((int) ($user->role ?? 0) === 4) {
            return true;
        }

        return (int) ($user->employee_id ?? 0) === (int) $employee->id;
    }

    private function leaveTypeOptions(): array
    {
        if (!Schema::hasTable('leave_types')) {
            return [];
        }

        return DB::table('leave_types')
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])
            ->all();
    }

    private function leaveTypeChoices(): array
    {
        return collect($this->leaveTypeOptions())
            ->map(fn ($name, $id) => [
                'id' => (int) $id,
                'name' => (string) $name,
            ])
            ->values()
            ->all();
    }

    private function formatDocumentFileSize(EmployeeDocument $document): ?string
    {
        $fileSize = $document->file_size_kb ?? $document->file_size ?? null;

        if ($fileSize === null) {
            return null;
        }

        $kilobytes = (float) $fileSize;

        if (($document->file_size ?? null) !== null && ($document->file_size_kb ?? null) === null) {
            $kilobytes = round($kilobytes / 1024, 2);
        }

        return number_format($kilobytes, $kilobytes >= 10 ? 0 : 2) . ' KB';
    }

    private function requestStatusLabel(int $status): string
    {
        switch ($status) {
            case 2:
                return 'Approved';
            case 3:
                return 'Rejected';
            default:
                return 'Pending';
        }
    }

    private function attendanceStatusLabel(int $status): string
    {
        switch ($status) {
            case 2:
                return 'Late';
            case 3:
                return 'Absent';
            case 4:
                return 'On Leave';
            default:
                return 'Present';
        }
    }

    private function payslipStatusLabel($status): string
    {
        if (is_numeric($status)) {
            switch ((int) $status) {
                case 2:
                    return 'Approved';
                case 3:
                    return 'Released';
                default:
                    return 'Draft';
            }
        }

        return ucfirst(strtolower((string) $status));
    }

    private function roleLabel(int $role): string
    {
        switch ($role) {
            case 1:
                return 'Employee';
            case 2:
                return 'Supervisor';
            case 4:
                return 'HR';
            default:
                return 'N/A';
        }
    }

    private function employeeStatusLabel(int $status): string
    {
        switch ($status) {
            case 1:
                return 'Active';
            case 2:
                return 'Probationary';
            case 3:
                return 'On Leave';
            case 4:
                return 'Resigned';
            case 5:
                return 'Terminated';
            default:
                return 'Unknown';
        }
    }
}
