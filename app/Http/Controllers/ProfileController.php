<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Leave;
use App\Models\Payslip;
use App\Models\Attendance;
use App\Support\UploadFilename;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the user profile form.
     */
    public function show()
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        $leaveRequests = collect();
        $attendanceLogs = collect();
        $payslips = collect();
        $documents = collect();
        $leaveTypes = $this->leaveTypeOptions();

        if ($employee) {
            $employee->load(['department', 'position', 'manager', 'documents', 'salaryRecords']);

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
                    ->where('emp_id', $employee->id)
                    ->where('punch_type', 'in')
                    ->latest('attendance_date')
                    ->latest('time')
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

            $payslips = Payslip::query()
                ->with(['payRun', 'lineItems'])
                ->where('employee_id', $employee->id)
                ->where(function ($query) {
                    $query->where('status', 2)
                        ->orWhere('status', 'approved')
                        ->orWhere('status', 3)
                        ->orWhere('status', 'completed')
                        ->orWhere('status', 'released');
                })
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
                ->sortByDesc(function ($document) {
                    return $document->uploaded_at ?? $document->created_at ?? $document->issued_date;
                })
                ->values()
                ->map(function ($document) {
                    return [
                        'name' => $document->display_name ?? $document->file_name,
                        'type' => $document->doc_type ?? $document->document_type ?? 'Document',
                        'date' => optional($document->uploaded_at ?? $document->created_at ?? $document->issued_date)->format('M d, Y'),
                        'file_path' => $document->file_url ?? $document->file_path ?? null,
                        'file_size' => $this->formatDocumentFileSize($document),
                    ];
                });
        }

        return view('profile.show', [
            'user' => $user,
            'employee' => $employee,
            'leaveRequests' => $leaveRequests,
            'attendanceLogs' => $attendanceLogs,
            'payslips' => $payslips,
            'documents' => $documents,
        ]);
    }

    /**
     * Update the user profile details and profile picture.
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Employees (role 1) cannot directly edit their profile.
        // They must file a profile update request through Self-Service.
        if ($user->role === 1) {
            return redirect()->route('profile.show')
                ->with('error', 'Employees cannot edit their profile directly. Please file a Profile Update Request through Self-Service.');
        }

        $employee = $user->employee;

        // Validation rules
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ];

        if ($employee) {
            $rules = array_merge($rules, [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:30'],
                'birth_date' => ['nullable', 'date'],
                'gender' => ['nullable', 'string', 'max:20'],
                'marital_status' => ['nullable', 'string', 'max:30'],
                'address_line1' => ['nullable', 'string', 'max:255'],
                'address_line2' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'province' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'country' => ['nullable', 'string', 'max:100'],
            ]);

            $rules['email'][] = Rule::unique('employees', 'email')->ignore($employee->id);
        }

        $validated = $request->validate($rules);

        // Update User
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($employee) {
            // Handle Profile Picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');

                // Delete old profile picture if exists
                if ($employee->profile_picture) {
                    Storage::disk('public')->delete($employee->profile_picture);
                }

                // Build filename: employeeCode_Lastname, Firstname M._Date_time
                $code = $employee->employee_code ?? $employee->id;
                $last = $employee->last_name ?? '';
                $first = $employee->first_name ?? '';
                $middle = $employee->middle_name ? strtoupper(substr($employee->middle_name, 0, 1)) . '.' : '';
                $datetime = now()->format('Ymd_His');
                $base = sprintf('%s_%s, %s %s_%s', $code, $last, $first, $middle, $datetime);

                // Remove characters invalid in filenames but preserve comma and spaces
                $safeBase = preg_replace('/[<>:\"\/\\|?\*\x00-\x1F]/', '', $base);
                $extension = strtolower((string) $file->getClientOriginalExtension());
                $filename = $safeBase . '.' . $extension;

                // Ensure target folder exists under external public root (micro)
                $publicRoot = config('filesystems.disks.public.root');
                if ($publicRoot) {
                    File::ensureDirectoryExists(rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'profile_pictures');
                }

                // Store with the custom filename
                $storedPath = $file->storeAs('profile_pictures', $filename, 'public');
                $employee->profile_picture = $storedPath;
            }

            // Update Employee Details
            $employee->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'address_line1' => $validated['address_line1'] ?? null,
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? null,
            ]);
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully.');
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

    private function formatDocumentFileSize($document): ?string
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
}
