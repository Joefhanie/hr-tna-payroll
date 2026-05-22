<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Payslip;
use App\Models\Leave;
use App\Models\PayRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard with metrics.
     */
    public function index()
    {
        // 1. Total Active Employees
        $activeEmployeesCount = Employee::where('status', 1)->count();

        // 2. Today's Attendance count (only those with a time in/check_in record)
        $todayAttendanceCount = Attendance::whereDate('attendance_date', Carbon::today())
            ->whereNotNull('check_in')
            ->count();

        // 3. Approved Leaves this month
        $approvedLeavesCount = Leave::where('status', 2)
            ->whereMonth('start_date', Carbon::today()->month)
            ->whereYear('start_date', Carbon::today()->year)
            ->count();

        // 4. Completed Pay Runs YTD
        $completedPayRunsCount = PayRun::where('status', 3)
            ->whereYear('period_start', Carbon::today()->year)
            ->count();

        $reports = [
            [
                'id' => 'headcount',
                'name' => 'Headcount',
                'category' => 'Workforce',
                'status' => 'Ready',
                'description' => 'Comprehensive directory of active, probationary, and inactive employees.',
            ],
            [
                'id' => 'attendance',
                'name' => 'Attendance',
                'category' => 'Timekeeping',
                'status' => 'Ready',
                'description' => 'Daily clock-in and clock-out summaries including late and leave status details.',
            ],
            [
                'id' => 'payroll',
                'name' => 'Payroll Summary',
                'category' => 'Finance',
                'status' => 'Ready',
                'description' => 'Summary of gross pay, government contributions, deductions, and net payouts.',
            ],
            [
                'id' => 'previous-claims',
                'name' => 'Previous Claims',
                'category' => 'Finance',
                'status' => 'Ready',
                'description' => 'Summary of previous claims, including employee, type, date, amount, status, and associated pay run.',
            ],
            [
                'id' => 'disputes',
                'name' => 'Payslip Disputes',
                'category' => 'Finance',
                'status' => 'Ready',
                'description' => 'Overview of employee payslip disputes, including amount, reason, status, and resolver notes.',
            ],
            [
                'id' => 'leave',
                'name' => 'Leave Utilization',
                'category' => 'People Ops',
                'status' => 'Ready',
                'description' => 'Overview of leave requests, balances used, types, and approval states.',
            ],
        ];

        return view('reports', compact(
            'reports',
            'activeEmployeesCount',
            'todayAttendanceCount',
            'approvedLeavesCount',
            'completedPayRunsCount'
        ));
    }

    /**
     * Download the specified report type as a CSV file.
     */
    public function download(string $type, Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'status'     => ['nullable', 'string', 'max:255'],
            'pay_run_id' => ['nullable', 'string', 'max:255'],
        ]);

        $now = Carbon::now()->format('Y-m-d');

        switch ($type) {
            case 'headcount':
                return $this->downloadHeadcountReport($now);
            case 'attendance':
                return $this->downloadAttendanceReport($now, $request);
            case 'payroll':
                return $this->downloadPayrollReport($now, $request);
            case 'previous-claims':
                return $this->downloadPreviousClaimsReport($now, $request);
            case 'disputes':
                return $this->downloadDisputesReport($now, $request);
            case 'leave':
                return $this->downloadLeaveReport($now, $request);
            default:
                return redirect()->route('reports')->with('error', 'Invalid report type selected.');
        }
    }

    /**
     * Export headcount report.
     */
    private function downloadHeadcountReport($date)
    {
        $headers = [
            'Employee ID',
            'Employee Code',
            'First Name',
            'Last Name',
            'Middle Name',
            'Email',
            'Phone',
            'Gender',
            'Birth Date',
            'Department',
            'Position',
            'Employment Type',
            'Hire Date',
            'Status',
            'Current Base Salary'
        ];

        $employees = Employee::with(['department', 'position', 'salaryRecords' => function ($q) {
            $q->orderBy('effective_date', 'desc');
        }])->get();

        $employmentTypes = [
            1 => 'Full-time',
            2 => 'Part-time',
            3 => 'Contractual',
            4 => 'Intern'
        ];

        $statusLabels = [
            1 => 'Active',
            2 => 'Probationary',
            3 => 'On Leave',
            4 => 'Resigned',
            5 => 'Terminated'
        ];

        return $this->streamCsv("headcount_report_{$date}.csv", $headers, function ($file) use ($employees, $employmentTypes, $statusLabels) {
            foreach ($employees as $employee) {
                $salary = $employee->salaryRecords->first()?->amount ?? '0.00';
                
                fputcsv($file, [
                    $employee->id,
                    $employee->employee_code ?? 'N/A',
                    $employee->first_name,
                    $employee->last_name,
                    $employee->middle_name ?? '',
                    $employee->email ?? '',
                    $employee->phone ?? '',
                    $employee->gender ?? '',
                    $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '',
                    $employee->department->name ?? 'N/A',
                    $employee->position->title ?? 'N/A',
                    $employmentTypes[$employee->employment_type] ?? 'Unknown',
                    $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '',
                    $statusLabels[$employee->status] ?? 'Unknown',
                    $salary
                ]);
            }
        });
    }

    /**
     * Export attendance report.
     */
    private function downloadAttendanceReport($date, Request $request)
    {
        $headers = [
            'Date',
            'Employee Code',
            'Employee Name',
            'Shift Name',
            'Shift Start',
            'Shift End',
            'Clock In',
            'Clock Out',
            'Status',
            'Notes'
        ];

        // Fetch attendance logs
        $query = Attendance::with(['user.employee', 'shift'])->orderByDesc('attendance_date');

        // Optional date filters
        if ($request->filled('start_date')) {
            $query->where('attendance_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('attendance_date', '<=', $request->end_date);
        }

        $attendances = $query->get();

        $statusLabels = [
            1 => 'Present',
            2 => 'Late',
            3 => 'Absent',
            4 => 'On Leave',
            5 => 'Shift Not Started'
        ];

        return $this->streamCsv("attendance_report_{$date}.csv", $headers, function ($file) use ($attendances, $statusLabels) {
            foreach ($attendances as $att) {
                $employee = $att->user?->employee;
                $empName = $employee ? $employee->full_name : ($att->user->name ?? 'N/A');
                $empCode = $employee ? ($employee->employee_code ?? 'N/A') : 'N/A';

                fputcsv($file, [
                    $att->attendance_date ? $att->attendance_date->format('Y-m-d') : 'N/A',
                    $empCode,
                    $empName,
                    $att->shift->name ?? 'N/A',
                    $att->shift->start_time ?? 'N/A',
                    $att->shift->end_time ?? 'N/A',
                    $att->check_in ? $att->check_in->format('H:i') : '',
                    $att->check_out ? $att->check_out->format('H:i') : '',
                    $statusLabels[$att->status] ?? $att->status,
                    $att->notes ?? ''
                ]);
            }
        });
    }

    /**
     * Export payroll summary report.
     */
    private function downloadPayrollReport($date, Request $request)
    {
        $headers = [
            'Pay Run Period',
            'Period Start',
            'Period End',
            'Pay Date',
            'Employee Code',
            'Employee Name',
            'Base Salary',
            'Gross Pay',
            'Total Deductions',
            'Net Pay',
            'Released At',
            'Status'
        ];

        $query = Payslip::with(['employee.salaryRecords', 'payRun']);

        if ($request->filled('pay_run_id')) {
            $query->where('pay_run_id', $request->pay_run_id);
        }

        $payslips = $query->get();

        $payRunStatuses = [
            1 => 'Draft',
            2 => 'Processing',
            3 => 'Completed',
            4 => 'Cancelled'
        ];

        return $this->streamCsv("payroll_summary_report_{$date}.csv", $headers, function ($file) use ($payslips, $payRunStatuses) {
            foreach ($payslips as $slip) {
                $employee = $slip->employee;
                $payRun = $slip->payRun;
                $baseSalary = $employee?->salaryRecords->first()?->amount ?? '0.00';

                fputcsv($file, [
                    $payRun->name ?? 'N/A',
                    $payRun->period_start ? $payRun->period_start->format('Y-m-d') : 'N/A',
                    $payRun->period_end ? $payRun->period_end->format('Y-m-d') : 'N/A',
                    $payRun->pay_date ? $payRun->pay_date->format('Y-m-d') : 'N/A',
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'N/A',
                    $baseSalary,
                    $slip->gross_pay,
                    $slip->total_deductions,
                    $slip->net_pay,
                    $slip->released_at ? $slip->released_at->format('Y-m-d H:i') : '',
                    $payRunStatuses[$payRun->status ?? 0] ?? 'Unknown'
                ]);
            }
        });
    }

    /**
     * Export leave utilization report.
     */
    private function downloadLeaveReport($date, Request $request)
    {
        $headers = [
            'Request ID',
            'Employee Code',
            'Employee Name',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days Requested',
            'Status',
            'Reason',
            'Approved By',
            'Approved At'
        ];

        $query = Leave::with(['employee', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        $leaves = $query->get();

        // Fetch leave types for mapping
        $leaveTypes = Schema::hasTable('leave_types')
            ? DB::table('leave_types')->get()->keyBy('id')
            : collect();

        $statusLabels = [
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            4 => 'Cancelled'
        ];

        return $this->streamCsv("leave_utilization_report_{$date}.csv", $headers, function ($file) use ($leaves, $leaveTypes, $statusLabels) {
            foreach ($leaves as $leave) {
                $employee = $leave->employee;
                $leaveTypeName = $leaveTypes[$leave->leave_type_id]->name ?? 'Leave Request';

                fputcsv($file, [
                    $leave->id,
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'N/A',
                    $leaveTypeName,
                    $leave->start_date ? $leave->start_date->format('Y-m-d') : 'N/A',
                    $leave->end_date ? $leave->end_date->format('Y-m-d') : 'N/A',
                    $leave->days_requested,
                    $statusLabels[$leave->status] ?? 'Unknown',
                    $leave->reason ?? '',
                    $leave->approver->name ?? 'N/A',
                    $leave->approved_at ? Carbon::parse($leave->approved_at)->format('Y-m-d H:i') : ''
                ]);
            }
        });
    }

    /**
     * Helper method to stream a CSV response.
     */
    private function streamCsv($filename, $headers, $callback)
    {
        $responseHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($headers, $callback) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, $headers);

            $callback($file);

            fclose($file);
        }, 200, $responseHeaders);
    }

    /**
     * Export previous claims report.
     */
    private function downloadPreviousClaimsReport($date, Request $request)
    {
        $headers = [
            'Claim ID',
            'Employee Code',
            'Employee Name',
            'Claim Type',
            'Claim Date',
            'Amount',
            'Description',
            'Status',
            'Pay Run Period',
            'Reviewed By',
            'Reviewed At',
            'HR Notes'
        ];

        // Fetch claims
        $query = \App\Models\PreviousClaim::with(['employee', 'reviewer', 'payRun'])
            ->latest('created_at');

        // Optional date filters (by claim_date)
        if ($request->filled('start_date')) {
            $query->where('claim_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('claim_date', '<=', $request->end_date);
        }

        $claims = $query->get();

        return $this->streamCsv("previous_claims_report_{$date}.csv", $headers, function ($file) use ($claims) {
            foreach ($claims as $claim) {
                $employee = $claim->employee;
                $payRun = $claim->payRun;
                $payRunLabel = $payRun 
                    ? $payRun->period_start->format('Y-m-d') . ' - ' . $payRun->period_end->format('Y-m-d') 
                    : 'N/A';

                fputcsv($file, [
                    $claim->id,
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'N/A',
                    $claim->claim_type,
                    $claim->claim_date ? $claim->claim_date->format('Y-m-d') : 'N/A',
                    $claim->amount,
                    $claim->description ?? '',
                    $claim->status_label,
                    $payRunLabel,
                    $claim->reviewer ? $claim->reviewer->name : 'N/A',
                    $claim->reviewed_at ? $claim->reviewed_at->format('Y-m-d H:i') : '',
                    $claim->hr_notes ?? ''
                ]);
            }
        });
    }

    /**
     * Export payslip disputes report.
     */
    private function downloadDisputesReport($date, Request $request)
    {
        $headers = [
            'Dispute ID',
            'Employee Code',
            'Employee Name',
            'Pay Run Period',
            'Dispute Amount',
            'Line Item',
            'Reason',
            'Status',
            'Resolved By',
            'Resolved At',
            'HR Notes'
        ];

        $statusLabels = [
            1 => 'Pending',
            2 => 'Resolved',
            3 => 'Rejected'
        ];

        // Fetch disputes
        $query = \App\Models\PayslipDispute::with(['employee', 'payslip.payRun', 'lineItem', 'resolver'])
            ->latest('created_at');

        // Optional date filters (by created_at)
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        $disputes = $query->get();

        return $this->streamCsv("payslip_disputes_report_{$date}.csv", $headers, function ($file) use ($disputes, $statusLabels) {
            foreach ($disputes as $dispute) {
                $employee = $dispute->employee;
                $payslip = $dispute->payslip;
                $payRun = $payslip?->payRun;
                $payRunPeriod = $payRun 
                    ? $payRun->period_start->format('Y-m-d') . ' - ' . $payRun->period_end->format('Y-m-d') 
                    : 'N/A';

                fputcsv($file, [
                    $dispute->id,
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'N/A',
                    $payRunPeriod,
                    $dispute->dispute_amount,
                    $dispute->lineItem ? $dispute->lineItem->description : 'Entire Payslip',
                    $dispute->dispute_reason,
                    $statusLabels[$dispute->status] ?? 'Unknown',
                    $dispute->resolver ? $dispute->resolver->name : 'N/A',
                    $dispute->resolved_at ? $dispute->resolved_at->format('Y-m-d H:i') : '',
                    $dispute->hr_notes ?? ''
                ]);
            }
        });
    }
}
