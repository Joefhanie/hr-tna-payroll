<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\ProfileUpdateRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isHrDashboard = (int) ($user?->role ?? 0) === 4;
        $today = Carbon::now()->toDateString();

        if (!$isHrDashboard) {
            $employee = $user?->employee;
            $employeeId = (int) ($employee?->id ?? 0);
            $userId = (int) ($user?->id ?? 0);

            $todayAttendanceRecord = $userId > 0
                ? Attendance::query()
                    ->where('user_id', $userId)
                    ->where('attendance_date', $today)
                    ->latest('attendance_date')
                    ->latest('id')
                    ->first()
                : null;

            $myPendingLeaves = $employeeId > 0
                ? Leave::query()->where('employee_id', $employeeId)->where('status', 1)->count()
                : 0;

            $myApprovedLeaves = $employeeId > 0
                ? Leave::query()
                    ->where('employee_id', $employeeId)
                    ->where('status', 2)
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->count()
                : 0;

            $profileUpdatePending = 0;
            if ($employeeId > 0 && Schema::hasTable('profile_update_requests')) {
                $profileUpdatePending = ProfileUpdateRequest::query()
                    ->where('employee_id', $employeeId)
                    ->where('status', 1)
                    ->count();
            }

            $releasedPayslips = $employeeId > 0
                ? Payslip::query()
                    ->where('employee_id', $employeeId)
                    ->where(function ($query) {
                        $query->where('status', 2)
                            ->orWhere('status', 'approved')
                            ->orWhere('status', 3)
                            ->orWhere('status', 'completed')
                            ->orWhere('status', 'released');
                    })
                    ->count()
                : 0;

            $todayAttendance = $userId > 0
                ? Attendance::with('user')
                    ->where('user_id', $userId)
                    ->latest('attendance_date')
                    ->latest('id')
                    ->limit(8)
                    ->get()
                : collect();

            $pendingLeaves = $employeeId > 0
                ? Leave::with('employee')
                    ->where('employee_id', $employeeId)
                    ->latest('created_at')
                    ->limit(8)
                    ->get()
                : collect();

            $leaveTypeNames = collect();
            if (Schema::hasTable('leave_types')) {
                $leaveTypeNames = DB::table('leave_types')->pluck('name', 'id');
            }

            return view('dashboard', [
                'user' => $user,
                'isHrDashboard' => false,
                'totalEmployees' => 0,
                'newHires' => 0,
                'onLeaveToday' => 0,
                'leavesPendingApproval' => 0,
                'totalPayroll' => 0,
                'payrollProcessing' => 0,
                'todayAttendance' => $todayAttendance,
                'pendingLeaves' => $pendingLeaves,
                'leaveTypeNames' => $leaveTypeNames,
                'todayAttendanceRecord' => $todayAttendanceRecord,
                'myPendingLeaves' => $myPendingLeaves,
                'myApprovedLeaves' => $myApprovedLeaves,
                'profileUpdatePending' => $profileUpdatePending,
                'releasedPayslips' => $releasedPayslips,
            ]);
        }

        // Total Employees (from employees table)
        $totalEmployees = Employee::where('status', 1)->count();

        // New Hires: employees whose hire_date falls within the last 30 days
        $newHires = Employee::whereBetween('hire_date', [
            Carbon::now()->subDays(30)->startOfDay(),
            Carbon::now()->endOfDay(),
        ])->count();

        // On Leave Today
        $onLeaveToday = Leave::where('status', 2)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();

        $leavesPendingApproval = Leave::where('status', 1)->count();

        // Current Payroll Status
        $currentMonth = Carbon::now();
        $totalPayroll = 0;
        $payrollProcessing = 0;

        if (Schema::hasTable('payrolls')) {
            $totalPayroll = Payroll::whereMonth('payroll_date', $currentMonth->month)
                ->whereYear('payroll_date', $currentMonth->year)
                ->sum('net_salary');

            $payrollProcessing = Payroll::whereMonth('payroll_date', $currentMonth->month)
                ->whereYear('payroll_date', $currentMonth->year)
                ->where('status', 1)
                ->count();
        }

        // Today's Attendance
        $todayAttendance = Attendance::with('user')
            ->where('attendance_date', $today)
            ->get();

        // Pending Leave Requests (status 1 = pending)
        $pendingLeaves = Leave::with('employee')
            ->where('status', 1)
            ->latest('created_at')
            ->get();

        // Load leave type names for display
        $leaveTypeNames = collect();
        if (Schema::hasTable('leave_types')) {
            $leaveTypeNames = DB::table('leave_types')->pluck('name', 'id');
        }

        return view('dashboard', [
            'user' => $user,
            'isHrDashboard' => true,
            'totalEmployees' => $totalEmployees,
            'newHires' => $newHires,
            'onLeaveToday' => $onLeaveToday,
            'leavesPendingApproval' => $leavesPendingApproval,
            'totalPayroll' => $totalPayroll,
            'payrollProcessing' => $payrollProcessing,
            'todayAttendance' => $todayAttendance,
            'pendingLeaves' => $pendingLeaves,
            'leaveTypeNames' => $leaveTypeNames,
            'todayAttendanceRecord' => null,
            'myPendingLeaves' => 0,
            'myApprovedLeaves' => 0,
            'profileUpdatePending' => 0,
            'releasedPayslips' => 0,
        ]);
    }
}
