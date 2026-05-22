<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\User;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Total Employees (from employees table)
        $totalEmployees = Employee::where('status', 1)->count();

        // New Hires: employees whose hire_date falls within the last 30 days
        $newHires = Employee::whereBetween('hire_date', [
            Carbon::now()->subDays(30)->startOfDay(),
            Carbon::now()->endOfDay(),
        ])->count();

        // On Leave Today
        $today = Carbon::now()->toDateString();
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
            'totalEmployees' => $totalEmployees,
            'newHires' => $newHires,
            'onLeaveToday' => $onLeaveToday,
            'leavesPendingApproval' => $leavesPendingApproval,
            'totalPayroll' => $totalPayroll,
            'payrollProcessing' => $payrollProcessing,
            'todayAttendance' => $todayAttendance,
            'pendingLeaves' => $pendingLeaves,
            'leaveTypeNames' => $leaveTypeNames,
        ]);
    }
}
