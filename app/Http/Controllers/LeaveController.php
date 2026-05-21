<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Display the main leave management page.
     */
    public function index(Request $request)
    {
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
            'q'      => trim((string) $request->string('q')),
            'status' => trim((string) $request->string('status')),
            'type'   => trim((string) $request->string('type')),
        ];

        // Build leave requests query
        $query = Leave::with(['employee', 'approver'])
            ->latest('created_at');

        // Employees only see their own leaves
        if ($user && $user->role === 1 && $user->employee_id) {
            $query->where('employee_id', $user->employee_id);
        }

        // Status filter
        if ($filters['status'] !== '') {
            $statusMap = ['pending' => 1, 'approved' => 2, 'rejected' => 3];
            if (isset($statusMap[$filters['status']])) {
                $query->where('status', $statusMap[$filters['status']]);
            }
        }

        // Leave type filter
        if ($filters['type'] !== '') {
            $query->where('leave_type_id', $filters['type']);
        }

        $leaveRequests = $query->get()->map(function (Leave $leave) use ($leaveTypes) {
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

        // Search filter
        if ($filters['q'] !== '') {
            $needle = mb_strtolower($filters['q']);
            $leaveRequests = $leaveRequests->filter(function ($r) use ($needle) {
                return str_contains(mb_strtolower($r['employee']), $needle)
                    || str_contains(mb_strtolower($r['type']), $needle);
            })->values();
        }

        return view('leave', [
            'balances'      => $balances,
            'leaveRequests' => $leaveRequests,
            'leaveTypes'    => $leaveTypes,
            'filters'       => $filters,
            'employees'     => $user && $user->role !== 1
                ? Employee::with('user')->orderBy('first_name')->get()
                : collect(),
        ]);
    }

    /**
     * Store a new leave request.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'employee_id'   => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:2000'],
        ]);

        // Employees can only file for themselves
        if ($user->role === 1 && (int) $validated['employee_id'] !== (int) $user->employee_id) {
            abort(403);
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate   = Carbon::parse($validated['end_date']);
        $days      = (float) ($startDate->diffInDays($endDate) + 1);

        Leave::create([
            'employee_id'   => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date'    => $startDate->toDateString(),
            'end_date'      => $endDate->toDateString(),
            'days_requested'=> $days,
            'reason'        => $validated['reason'] ?? null,
            'status'        => 1, // Pending
        ]);

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

        // All leave requests that overlap with this month
        $leaveRequests = Leave::with('employee')
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

        // Build a map: date => [ array of leave entries active on that day ]
        $calendarData   = [];
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
                        'type'     => $leaveTypes[$leave->leave_type_id]->name ?? 'Leave',
                        'status'   => (int) $leave->status,
                        'status_label' => match ((int) $leave->status) { 2 => 'Approved', 3 => 'Rejected', default => 'Pending' },
                        'days'     => (float) $leave->days_requested,
                    ];
                }
            }

            $cursor->addDay();
        }

        $totalEmployees = Employee::count();

        return view('leave.calendar', [
            'calendarData'       => $calendarData,
            'selectedDate'       => $selectedDate,
            'selectedDateCarbon' => $selectedDateCarbon,
            'totalEmployees'     => $totalEmployees,
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

        $leave->update([
            'status'         => 3,
            'rejection_note' => $validated['rejection_note'] ?? null,
        ]);

        return redirect()->route('leave.index')
            ->with('success', 'Leave request declined.');
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
            default => 'Pending',
        };
    }
}
