<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Services\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService
    ) {
    }

    /**
     * Display the main leave management page.
     */
    /**
     * Build the query for index and export.
     */
    private function buildQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Leave::with(['employee', 'approver'])
            ->latest('created_at');

        // Employees only see their own leaves
        if ($user && $user->role === 1 && $user->employee_id) {
            $query->where('employee_id', $user->employee_id);
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($subQuery) use ($q) {
                $subQuery->whereHas('employee', function ($empQuery) use ($q) {
                    $empQuery->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('middle_name', 'like', "%{$q}%")
                        ->orWhere('employee_code', 'like', "%{$q}%");
                });
            });
        }

        if ($request->filled('status')) {
            $statusMap = ['pending' => 1, 'approved' => 2, 'rejected' => 3, 'cancelled' => 4];
            $statusVal = $request->input('status');
            if (isset($statusMap[$statusVal])) {
                $query->where('status', $statusMap[$statusVal]);
            }
        }

        if ($request->filled('type')) {
            $query->where('leave_type_id', $request->input('type'));
        }

        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->input('end_date'));
        }

        return $query;
    }

    /**
     * Display the main leave management page.
     */
    public function index(Request $request)
    {
        $request->validate([
            'q'          => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'string', 'in:pending,approved,rejected,cancelled'],
            'type'       => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Fetch leave types
        $leaveTypes = [];
        if (Schema::hasTable('leave_types')) {
            $leaveTypes = DB::table('leave_types')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get();
        }

        // Fetch leave balances for the logged-in employee (current year)
        $balances = collect();
        $currentYear = now()->year;

        if ($user && $user->employee_id && Schema::hasTable('leave_balances')) {
            $balances = DB::table('leave_balances')
                ->join('leave_types', 'leave_balances.leave_type_id', '=', 'leave_types.id')
                ->where('leave_balances.employee_id', $user->employee_id)
                ->where('leave_balances.year', $currentYear)
                ->select(
                    'leave_types.id',
                    'leave_types.name',
                    'leave_types.max_days_per_year',
                    'leave_balances.entitled_days',
                    'leave_balances.used_days',
                    'leave_balances.accrued_days',
                    'leave_balances.carried_over'
                )
                ->get();
        }

        // If HR (role=4) or Supervisor (role=2), show summary balances across all employees
        if ($user && in_array($user->role, [2, 4]) && $balances->isEmpty() && !empty($leaveTypes)) {
            $balances = collect($leaveTypes)->map(function ($lt) {
                return (object) [
                    'id'             => $lt->id,
                    'name'           => $lt->name,
                    'max_days_per_year' => $lt->max_days_per_year,
                    'entitled_days'  => $lt->max_days_per_year,
                    'used_days'      => 0,
                    'accrued_days'   => 0,
                    'carried_over'   => 0,
                ];
            });
        }

        // Filters
        $filters = [
            'q'          => trim((string) $request->string('q')),
            'status'     => trim((string) $request->string('status')),
            'type'       => trim((string) $request->string('type')),
            'start_date' => trim((string) $request->string('start_date')),
            'end_date'   => trim((string) $request->string('end_date')),
        ];

        // Paginate the query
        $leaveRequestsPaginated = $this->buildQuery($request)
            ->paginate(15)
            ->appends($request->query());

        $leaveRequests = collect($leaveRequestsPaginated->items())->map(function (Leave $leave) use ($leaveTypes) {
            $leaveTypeName = collect($leaveTypes)->firstWhere('id', $leave->leave_type_id)?->name ?? 'Leave Request';
            $employee = $leave->employee;

            return [
                'id'         => $leave->id,
                'employee'   => $employee ? $employee->full_name : 'Unknown',
                'employee_id'=> $employee?->id,
                'type'       => $leaveTypeName,
                'type_id'    => $leave->leave_type_id,
                'from'       => optional($leave->start_date)->format('M d'),
                'from_full'  => optional($leave->start_date)->format('M d, Y'),
                'to'         => optional($leave->end_date)->format('M d'),
                'to_full'    => optional($leave->end_date)->format('M d, Y'),
                'days'       => (float) $leave->days_requested,
                'reason'     => $leave->reason,
                'status'     => $this->statusLabel((int) $leave->status),
                'status_code'=> (int) $leave->status,
                'rejection_note' => $leave->rejection_note,
            ];
        });

        return view('leave', [
            'balances'               => $balances,
            'leaveRequests'          => $leaveRequests,
            'leaveRequestsPaginated' => $leaveRequestsPaginated,
            'leaveTypes'             => $leaveTypes,
            'filters'                => $filters,
            'employees'              => $user && $user->role !== 1
                ? Employee::with('user')->orderBy('first_name')->get()
                : collect(),
        ]);
    }

    /**
     * Export leaves to CSV.
     */
    public function export(Request $request)
    {
        $request->validate([
            'q'          => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'string', 'in:pending,approved,rejected,cancelled'],
            'type'       => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $leaves = $this->buildQuery($request)->get();
        $filename = "leave_requests_export_" . now()->format('Ymd_His') . ".csv";

        $responseHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        // Fetch leave types for labels
        $leaveTypes = [];
        if (Schema::hasTable('leave_types')) {
            $leaveTypes = DB::table('leave_types')
                ->where('is_active', 1)
                ->get()
                ->keyBy('id');
        }

        return response()->stream(function () use ($leaves, $leaveTypes) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'Request ID',
                'Employee Code',
                'Employee Name',
                'Leave Type',
                'Start Date',
                'End Date',
                'Days Requested',
                'Status',
                'Reason',
                'Approver/Resolver',
                'Rejection Note/Cancellation Reason'
            ]);

            $statusLabels = [
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                4 => 'Cancelled'
            ];

            foreach ($leaves as $leave) {
                $employee = $leave->employee;
                $leaveTypeName = isset($leaveTypes[$leave->leave_type_id]) ? $leaveTypes[$leave->leave_type_id]->name : 'Leave';
                
                fputcsv($file, [
                    $leave->id,
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'N/A',
                    $leaveTypeName,
                    optional($leave->start_date)->toDateString() ?? '',
                    optional($leave->end_date)->toDateString() ?? '',
                    $leave->days_requested,
                    $statusLabels[$leave->status] ?? 'Unknown',
                    $leave->reason ?? '',
                    $leave->approver ? $leave->approver->name : '',
                    $leave->rejection_note ?? ''
                ]);
            }

            fclose($file);
        }, 200, $responseHeaders);
    }

    /**
     * Store a new leave request.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate(LeaveRequestService::rules());

        $this->leaveRequestService->submit($user, $validated);

        return redirect()->route('leave.index')
            ->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Approve a leave request.
     */
    public function approve(Leave $leave): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $leave->update([
            'status'      => 2,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_note' => null,
        ]);

        // Update leave balance
        $this->updateBalance($leave, 'use');

        // Automatically update employee status to 3 (On Leave) if leave covers today
        $today = now()->startOfDay();
        $start = Carbon::parse($leave->start_date)->startOfDay();
        $end   = Carbon::parse($leave->end_date)->startOfDay();

        if ($today->between($start, $end)) {
            $leave->employee()->update(['status' => 3]);
        }

        return redirect()->route('leave.index')
            ->with('success', 'Leave request approved.');
    }

    /**
     * Leave Calendar view — shows per-day leave status for the whole month.
     */
    public function calendarView(Request $request)
    {
        $selectedDate        = $request->query('date', now()->toDateString());
        $selectedDateCarbon  = Carbon::parse($selectedDate);

        $startOfMonth = $selectedDateCarbon->copy()->startOfMonth()->toDateString();
        $endOfMonth   = $selectedDateCarbon->copy()->endOfMonth()->toDateString();

        // All leave requests that overlap with this month (excluding Cancelled status)
        $leaveRequests = Leave::with('employee')
            ->where('status', '!=', 4)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                      $q2->where('start_date', '<=', $startOfMonth)
                         ->where('end_date', '>=', $endOfMonth);
                  });
            })
            ->get();

        $leaveTypes = Schema::hasTable('leave_types')
            ? DB::table('leave_types')->where('is_active', 1)->get()->keyBy('id')
            : collect();

        $employees = Employee::with(['shiftAssignments.shift'])->get();

        $getActiveShiftForDateInMemory = function ($employee, $date) {
            $dayOfWeek = $date->format('D');

            $assignments = $employee->shiftAssignments
                ->filter(function ($assignment) use ($date) {
                    return $assignment->isActiveOn($date);
                })
                ->sortByDesc('effective_from');

            foreach ($assignments as $assignment) {
                if ($assignment->shift && is_array($assignment->shift->days_of_week)) {
                    if (in_array($dayOfWeek, $assignment->shift->days_of_week)) {
                        return $assignment->shift;
                    }
                }
            }

            return $assignments->first()?->shift;
        };

        // Build a map: date => [ array of leave entries active on that day ]
        $calendarData   = [];
        $availableCounts = [];
        $cursor         = Carbon::parse($startOfMonth);
        $endCarbon      = Carbon::parse($endOfMonth);

        while ($cursor->lte($endCarbon)) {
            $dateStr = $cursor->toDateString();
            $calendarData[$dateStr] = [];

            foreach ($leaveRequests as $leave) {
                if ($leave->start_date && $leave->end_date
                    && $leave->start_date->lte($cursor)
                    && $leave->end_date->gte($cursor)) {
                    $calendarData[$dateStr][] = [
                        'id'       => $leave->id,
                        'employee' => $leave->employee?->full_name ?? 'Unknown',
                        'employee_id' => $leave->employee_id,
                        'type'     => $leaveTypes[$leave->leave_type_id]->name ?? 'Leave',
                        'status'   => (int) $leave->status,
                        'status_label' => match ((int) $leave->status) { 2 => 'Approved', 3 => 'Rejected', default => 'Pending' },
                        'days'     => (float) $leave->days_requested,
                    ];
                }
            }

            $approvedLeaveEmployeeIds = collect($calendarData[$dateStr])
                ->where('status', 2)
                ->pluck('employee_id')
                ->all();

            $availableCount = 0;
            foreach ($employees as $employee) {
                $dayShift = $getActiveShiftForDateInMemory($employee, $cursor);
                $isScheduled = $dayShift && is_array($dayShift->days_of_week) && in_array($cursor->format('D'), $dayShift->days_of_week);
                $isOnLeave = in_array($employee->id, $approvedLeaveEmployeeIds);

                if ($isScheduled && !$isOnLeave) {
                    $availableCount++;
                }
            }
            $availableCounts[$dateStr] = $availableCount;

            $cursor->addDay();
        }

        $totalEmployees = Employee::count();

        return view('leave.calendar', [
            'calendarData'       => $calendarData,
            'selectedDate'       => $selectedDate,
            'selectedDateCarbon' => $selectedDateCarbon,
            'totalEmployees'     => $totalEmployees,
            'availableCounts'    => $availableCounts,
            'prevMonthDate'      => $selectedDateCarbon->copy()->subMonth()->startOfMonth()->toDateString(),
            'nextMonthDate'      => $selectedDateCarbon->copy()->addMonth()->startOfMonth()->toDateString(),
        ]);
    }

    /**
     * Decline a leave request.
     */
    public function decline(Request $request, Leave $leave): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = (int) $leave->status;

        $leave->update([
            'status'         => 3,
            'rejection_note' => $validated['rejection_note'] ?? null,
        ]);

        // If it was approved, restore balance and revert employee status if it covered today
        if ($oldStatus === 2) {
            $this->updateBalance($leave, 'restore');

            $today = now()->startOfDay();
            $start = Carbon::parse($leave->start_date)->startOfDay();
            $end   = Carbon::parse($leave->end_date)->startOfDay();

            if ($today->between($start, $end)) {
                $leave->employee()->update(['status' => 1]); // Revert to Active
            }
        }

        return redirect()->route('leave.index')
            ->with('success', 'Leave request declined.');
    }

    /**
     * Cancel an approved or pending leave request.
     */
    public function cancel(Request $request, Leave $leave): RedirectResponse
    {
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $oldStatus = (int) $leave->status;

        $leave->update([
            'status'         => 4, // Cancelled
            'rejection_note' => $validated['cancellation_reason'],
        ]);

        // If the leave was previously approved, restore balance and revert employee status if it covered today
        if ($oldStatus === 2) {
            $this->updateBalance($leave, 'restore');

            $today = now()->startOfDay();
            $start = Carbon::parse($leave->start_date)->startOfDay();
            $end   = Carbon::parse($leave->end_date)->startOfDay();

            if ($today->between($start, $end)) {
                $leave->employee()->update(['status' => 1]); // Revert to Active
            }
        }

        return redirect()->route('leave.index')
            ->with('success', 'Leave request cancelled successfully.');
    }

    /**
     * Update leave balance used_days when a leave is approved.
     */
    private function updateBalance(Leave $leave, string $action): void
    {
        if (!Schema::hasTable('leave_balances')) {
            return;
        }

        $year = optional($leave->start_date)->year ?? now()->year;
        $days = (float) $leave->days_requested;

        $balance = DB::table('leave_balances')
            ->where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            DB::table('leave_balances')
                ->where('id', $balance->id)
                ->update([
                    'used_days'  => max(0, (float) $balance->used_days + ($action === 'use' ? $days : -$days)),
                    'updated_at' => now(),
                ]);
        }
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            2       => 'Approved',
            3       => 'Rejected',
            4       => 'Cancelled',
            default => 'Pending',
        };
    }
}
