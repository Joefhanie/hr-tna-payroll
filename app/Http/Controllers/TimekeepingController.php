<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\Shift;
use App\Services\LateDeductionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TimekeepingController extends Controller
{
    /**
     * Get the list of employee IDs the current user is authorized to view.
     */
    private function getAllowedEmployeeIds(User $user): ?array
    {
        if ($user->role === 4) {
            return null; // HR can view all
        }

        $employeeIds = [];
        if ($user->employee_id) {
            $employeeIds[] = (int) $user->employee_id;
        }

        if (($user->role === 2 || $user->role === 3) && $user->employee_id) {
            $subordinateIds = Employee::where('manager_id', $user->employee_id)->pluck('id')->map(fn($id) => (int) $id)->toArray();
            $employeeIds = array_merge($employeeIds, $subordinateIds);
        }

        return $employeeIds;
    }

    /**
     * Build the attendance list for the selected date, including actual and virtual records.
     */
    private function buildTodayAttendance(Request $request)
    {
        $selectedDate = $request->query('date', Carbon::now()->toDateString());

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $allowedEmployeeIds = $this->getAllowedEmployeeIds($user);

        $employeesQuery = Employee::with(['user', 'currentShift.shift', 'currentShifts.shift']);
        if ($allowedEmployeeIds !== null) {
            $employeesQuery->whereIn('id', $allowedEmployeeIds);
        }

        $employees = Schema::hasTable('employees')
            ? $employeesQuery->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name')->get()
            : collect();

        $attendanceQuery = Attendance::with(['user.employee.currentShift.shift', 'shift'])
            ->where('attendance_date', $selectedDate)
            ->where('punch_type', 'in');

        if ($allowedEmployeeIds !== null) {
            $attendanceQuery->whereIn('emp_id', $allowedEmployeeIds);
        }

        $todayAttendance = Schema::hasTable('attendance')
            ? $attendanceQuery->orderBy('time')->get()->unique(fn ($attendance) => $attendance->emp_id . '|' . $attendance->attendance_date->toDateString())
            : collect();

        // Helper function to insert virtual "Shift Not Started" records
        $addVirtualShiftNotStarted = function ($attendanceCollection, $employees, $startDate, $endDate = null) {
            $endDate = $endDate ?: $startDate;
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            $existingMap = [];
            foreach ($attendanceCollection as $att) {
                $dateStr = $att->attendance_date->toDateString();
                $existingMap[$dateStr][$att->emp_id] = true;
            }

            $virtualRecords = [];
            $nowManila = now('Asia/Manila');

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $dateStr = $cursor->toDateString();
                $dayOfWeek = $cursor->format('D');

                foreach ($employees as $employee) {
                    $user = $employee->user;
                    if (!$user) continue;

                    if (isset($existingMap[$dateStr][$user->id])) {
                        continue;
                    }

                    $dayShift = $employee->getActiveShiftForDate($cursor);

                    if ($dayShift && is_array($dayShift->days_of_week) && in_array($dayOfWeek, $dayShift->days_of_week)) {
                        $isFutureDate = $cursor->gt(now('Asia/Manila')->startOfDay());
                        $isToday = $cursor->isToday();

                        $hasNotStarted = false;
                        if ($isFutureDate) {
                            $hasNotStarted = true;
                        } elseif ($isToday) {
                            $shiftStart = Carbon::parse($dateStr . ' ' . $dayShift->start_time, 'Asia/Manila');
                            if ($nowManila->lte($shiftStart)) {
                                $hasNotStarted = true;
                            }
                        }

                        $hasApprovedLeave = \Illuminate\Support\Facades\DB::table('leave_requests')
                            ->where('employee_id', $employee->id)
                            ->where('status', 2) // Approved
                            ->where('start_date', '<=', $dateStr)
                            ->where('end_date', '>=', $dateStr)
                            ->exists();

                        if ($hasApprovedLeave) {
                            $virtual = new Attendance([
                                'emp_id' => $employee->id,
                                'shift_id' => $dayShift->id,
                                'attendance_date' => $cursor->copy(),
                                'punch_type' => 'in',
                                'time' => null,
                                'status' => 4, // On Leave
                                'notes' => 'Auto-marked: Approved Leave'
                            ]);
                            $virtual->setRelation('user', $user);
                            $virtual->setRelation('shift', $dayShift);
                            $virtualRecords[] = $virtual;
                        } elseif ($hasNotStarted) {
                            $virtual = new Attendance([
                                'emp_id' => $employee->id,
                                'shift_id' => $dayShift->id,
                                'attendance_date' => $cursor->copy(),
                                'punch_type' => 'in',
                                'time' => null,
                                'status' => 5, // Shift Not Started
                                'notes' => 'Shift has not started yet.'
                            ]);
                            $virtual->setRelation('user', $user);
                            $virtual->setRelation('shift', $dayShift);
                            $virtualRecords[] = $virtual;
                        }
                    }
                }
                $cursor->addDay();
            }

            return $attendanceCollection->concat($virtualRecords);
        };

        $todayAttendance = $addVirtualShiftNotStarted($todayAttendance, $employees, $selectedDate);

        // Enforce approved leave priority over any status in view
        $allApprovedLeaves = \Illuminate\Support\Facades\DB::table('leave_requests')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('leave_requests.status', 2) // Approved
            ->select('leave_requests.*', 'leave_types.is_paid')
            ->get();

        $leaveLookup = [];
        $paidLeaveLookup = [];
        foreach ($allApprovedLeaves as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            $c = $start->copy();
            while ($c->lte($end)) {
                $dateKey = $c->toDateString();
                $leaveLookup[$leave->employee_id][$dateKey] = true;
                if ($leave->is_paid) {
                    $paidLeaveLookup[$leave->employee_id][$dateKey] = true;
                }
                $c->addDay();
            }
        }

        foreach ($todayAttendance as $att) {
            $employee = $att->user?->employee;
            if ($employee) {
                $dateStr = $att->attendance_date->toDateString();
                $isPaidLeave = isset($paidLeaveLookup[$employee->id][$dateStr]);
                $isAnyLeave = isset($leaveLookup[$employee->id][$dateStr]);

                $shouldOverride = false;
                // If no check-in/out, always override with leave
                if (is_null($att->check_in) && is_null($att->check_out) && $isAnyLeave) {
                    $shouldOverride = true;
                }
                // If they timed in, but it's a paid leave, override with paid leave
                elseif ($isPaidLeave) {
                    $shouldOverride = true;
                }

                if ($shouldOverride) {
                    $att->status = 4; // On Leave
                    $att->notes = $isPaidLeave ? 'Approved Paid Leave (Priority Override)' : 'Approved Leave (Priority Override)';
                }
            }
        }

        return $todayAttendance;
    }

    public function index(Request $request)
    {
        $request->validate([
            'date'   => ['nullable', 'date'],
            'q'      => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:1,2,3,4,5'],
        ]);

        $selectedDate = $request->query('date', Carbon::now()->toDateString());
        $selectedDateCarbon = Carbon::parse($selectedDate);

        // Get unfiltered list for summary counts and client-side filtering in index view
        $unfilteredAttendance = $this->buildTodayAttendance($request);
        $todayAttendance = $unfilteredAttendance;

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $allowedEmployeeIds = $this->getAllowedEmployeeIds($user);

        $employeesQuery = Employee::with(['user', 'currentShift.shift', 'currentShifts.shift']);
        if ($allowedEmployeeIds !== null) {
            $employeesQuery->whereIn('id', $allowedEmployeeIds);
        }

        $employees = Schema::hasTable('employees')
            ? $employeesQuery->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name')->get()
            : collect();

        $calendarDataRecordsQuery = Attendance::with(['user.employee.currentShift.shift', 'shift'])
            ->whereBetween('attendance_date', [
                $selectedDateCarbon->copy()->startOfMonth()->toDateString(),
                $selectedDateCarbon->copy()->endOfMonth()->toDateString()
            ])
            ->where('punch_type', 'in');

        if ($allowedEmployeeIds !== null) {
            $calendarDataRecordsQuery->whereIn('emp_id', $allowedEmployeeIds);
        }

        $calendarDataRecords = Schema::hasTable('attendance')
            ? $calendarDataRecordsQuery->orderBy('time')->get()->unique(fn ($attendance) => $attendance->emp_id . '|' . $attendance->attendance_date->toDateString())
            : collect();

        // Helper function to insert virtual "Shift Not Started" records for calendar
        $addVirtualShiftNotStarted = function ($attendanceCollection, $employees, $startDate, $endDate = null) {
            $endDate = $endDate ?: $startDate;
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            $existingMap = [];
            foreach ($attendanceCollection as $att) {
                $dateStr = $att->attendance_date->toDateString();
                $existingMap[$dateStr][$att->emp_id] = true;
            }

            $virtualRecords = [];
            $nowManila = now('Asia/Manila');

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $dateStr = $cursor->toDateString();
                $dayOfWeek = $cursor->format('D');

                foreach ($employees as $employee) {
                    $user = $employee->user;
                    if (!$user) continue;

                    if (isset($existingMap[$dateStr][$user->id])) {
                        continue;
                    }

                    $dayShift = $employee->getActiveShiftForDate($cursor);

                    if ($dayShift && is_array($dayShift->days_of_week) && in_array($dayOfWeek, $dayShift->days_of_week)) {
                        $isFutureDate = $cursor->gt(now('Asia/Manila')->startOfDay());
                        $isToday = $cursor->isToday();

                        $hasNotStarted = false;
                        if ($isFutureDate) {
                            $hasNotStarted = true;
                        } elseif ($isToday) {
                            $shiftStart = Carbon::parse($dateStr . ' ' . $dayShift->start_time, 'Asia/Manila');
                            if ($nowManila->lte($shiftStart)) {
                                $hasNotStarted = true;
                            }
                        }

                        $hasApprovedLeave = \Illuminate\Support\Facades\DB::table('leave_requests')
                            ->where('employee_id', $employee->id)
                            ->where('status', 2) // Approved
                            ->where('start_date', '<=', $dateStr)
                            ->where('end_date', '>=', $dateStr)
                            ->exists();

                        if ($hasApprovedLeave) {
                            $virtual = new Attendance([
                                'user_id' => $user->id,
                                'shift_id' => $dayShift->id,
                                'attendance_date' => $cursor->copy(),
                                'status' => 4, // On Leave
                                'check_in' => null,
                                'check_out' => null,
                                'notes' => 'Auto-marked: Approved Leave'
                            ]);
                            $virtual->setRelation('user', $user);
                            $virtual->setRelation('shift', $dayShift);
                            $virtualRecords[] = $virtual;
                        } elseif ($hasNotStarted) {
                            $virtual = new Attendance([
                                'user_id' => $user->id,
                                'shift_id' => $dayShift->id,
                                'attendance_date' => $cursor->copy(),
                                'status' => 5, // Shift Not Started
                                'check_in' => null,
                                'check_out' => null,
                                'notes' => 'Shift has not started yet.'
                            ]);
                            $virtual->setRelation('user', $user);
                            $virtual->setRelation('shift', $dayShift);
                            $virtualRecords[] = $virtual;
                        }
                    }
                }
                $cursor->addDay();
            }

            return $attendanceCollection->concat($virtualRecords);
        };

        $calendarDataRecords = $addVirtualShiftNotStarted(
            $calendarDataRecords,
            $employees,
            $selectedDateCarbon->copy()->startOfMonth()->toDateString(),
            $selectedDateCarbon->copy()->endOfMonth()->toDateString()
        );

        $allApprovedLeaves = \Illuminate\Support\Facades\DB::table('leave_requests')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('leave_requests.status', 2) // Approved
            ->select('leave_requests.*', 'leave_types.is_paid')
            ->get();

        $leaveLookup = [];
        $paidLeaveLookup = [];
        foreach ($allApprovedLeaves as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            $c = $start->copy();
            while ($c->lte($end)) {
                $dateKey = $c->toDateString();
                $leaveLookup[$leave->employee_id][$dateKey] = true;
                if ($leave->is_paid) {
                    $paidLeaveLookup[$leave->employee_id][$dateKey] = true;
                }
                $c->addDay();
            }
        }

        $enforceLeavePriority = function ($records) use ($leaveLookup, $paidLeaveLookup) {
            foreach ($records as $att) {
                $employee = $att->user?->employee;
                if ($employee) {
                    $dateStr = $att->attendance_date->toDateString();
                    $isPaidLeave = isset($paidLeaveLookup[$employee->id][$dateStr]);
                    $isAnyLeave = isset($leaveLookup[$employee->id][$dateStr]);

                    $shouldOverride = false;
                    if (is_null($att->check_in) && is_null($att->check_out) && $isAnyLeave) {
                        $shouldOverride = true;
                    }
                    elseif ($isPaidLeave) {
                        $shouldOverride = true;
                    }

                    if ($shouldOverride) {
                        $att->status = 4; // On Leave
                        $att->notes = $isPaidLeave ? 'Approved Paid Leave (Priority Override)' : 'Approved Leave (Priority Override)';
                    }
                }
            }
        };

        $enforceLeavePriority($calendarDataRecords);

        // Ensure each record's user has a precomputed `display_name` attribute
        // so client-side code can rely on `record.user.display_name` existing.
        foreach ($calendarDataRecords as $att) {
            $user = $att->user;
            if ($user) {
                $computed = optional($user->employee)->full_name ?? ($user->display_name ?? $user->name ?? 'Unknown');
                $user->setAttribute('display_name', $computed);
            }
            // Also set a top-level computed display name on the attendance record
            $attDisplay = optional($att->user?->employee)->full_name ?? ($att->user?->display_name ?? $att->user?->name ?? 'Unknown');
            $att->setAttribute('employee_display_name', $attDisplay);
        }

        $calendarData = $calendarDataRecords->groupBy(function ($attendance) {
            return $attendance->attendance_date->toDateString();
        });

        $recentAttendance = Schema::hasTable('attendance')
            ? Attendance::with(['user.employee.currentShift.shift', 'shift'])
                ->where('punch_type', 'in')
                ->whereIn('emp_id', $unfilteredAttendance->pluck('emp_id')->unique())
                ->where('attendance_date', '>=', Carbon::now()->subDays(30)->toDateString())
                ->orderByDesc('attendance_date')
                ->orderByDesc('time')
                ->get()
                ->unique(fn ($attendance) => $attendance->emp_id.'|'.$attendance->attendance_date->toDateString())
                ->groupBy('emp_id')
            : collect();

        $attendanceStatusLabels = [
            1 => 'Present',
            2 => 'Late',
            3 => 'Absent',
            4 => 'On Leave',
            5 => 'Shift Not Started',
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'excused' => 'On Leave',
            'not_started' => 'Shift Not Started',
        ];

        $normalizeStatus = function ($status) {
            if (is_numeric($status)) {
                return (int) $status;
            }

            return strtolower((string) $status);
        };

        $statusCounts = $unfilteredAttendance->countBy(function ($attendance) use ($normalizeStatus) {
            return $normalizeStatus($attendance->status);
        });

        $presentToday = (int) ($statusCounts[1] ?? $statusCounts['present'] ?? 0);
        $lateToday = (int) ($statusCounts[2] ?? $statusCounts['late'] ?? 0);
        $absentToday = (int) ($statusCounts[3] ?? $statusCounts['absent'] ?? 0);
        $onLeaveToday = (int) ($statusCounts[4] ?? $statusCounts['excused'] ?? 0);

        $activeAttendance = $todayAttendance->first(function ($attendance) use ($normalizeStatus) {
            $status = $normalizeStatus($attendance->status);

            return in_array($status, [1, 'present', 2, 'late'], true) && $attendance->check_in;
        }) ?? $todayAttendance->first();

        $openAttendanceMap = Schema::hasTable('attendance')
            ? Attendance::where('punch_type', 'in')
                ->get()
                ->filter(function ($attendance) {
                    return is_null($attendance->check_out);
                })
                ->mapWithKeys(function ($attendance) {
                    return [
                        $attendance->emp_id . '|' . $attendance->attendance_date->toDateString() => $attendance->check_in?->format('H:i'),
                    ];
                })
                ->all()
            : [];

        $filters = $request->only(['q', 'status']);

        return view('timekeeping.index', [
            'todayAttendance' => $todayAttendance,
            'attendanceStatusLabels' => $attendanceStatusLabels,
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'absentToday' => $absentToday,
            'onLeaveToday' => $onLeaveToday,
            'activeAttendance' => $activeAttendance,
            'todayDate' => Carbon::now(),
            'selectedDate' => $selectedDate,
            'recentAttendance' => $recentAttendance,
            'users' => $employees,
            'openAttendanceMap' => $openAttendanceMap,
            'calendarData' => $calendarData,
            'filters' => $filters,
        ]);
    }

    /**
     * Export attendance logs to CSV.
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->role !== 4) {
            abort(403, 'Unauthorized action. Exports are restricted to HR only.');
        }

        $request->validate([
            'date'   => ['nullable', 'date'],
            'q'      => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:1,2,3,4,5'],
        ]);

        $selectedDate = $request->query('date', Carbon::now()->toDateString());
        $todayAttendance = $this->buildTodayAttendance($request);

        if ($request->filled('q')) {
            $qLower = strtolower($request->input('q'));
            $todayAttendance = $todayAttendance->filter(function ($att) use ($qLower) {
                $emp = $att->user?->employee;
                if (!$emp) return false;
                return str_contains(strtolower($emp->first_name), $qLower)
                    || str_contains(strtolower($emp->last_name), $qLower)
                    || str_contains(strtolower($emp->middle_name), $qLower)
                    || str_contains(strtolower($emp->employee_code), $qLower)
                    || str_contains(strtolower($emp->email), $qLower);
            });
        }

        if ($request->filled('status')) {
            $statusVal = (int) $request->input('status');
            $todayAttendance = $todayAttendance->filter(function ($att) use ($statusVal) {
                return ((int) $att->status) === $statusVal;
            });
        }

        $filename = "attendance_export_" . $selectedDate . "_" . now()->format('Ymd_His') . ".csv";

        $responseHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($todayAttendance, $selectedDate) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Date',
                'Employee Code',
                'Employee Name',
                'Shift',
                'Check In',
                'Check Out',
                'Status',
                'Notes'
            ]);

            $statusLabels = [
                1 => 'Present',
                2 => 'Late',
                3 => 'Absent',
                4 => 'On Leave',
                5 => 'Shift Not Started'
            ];

            foreach ($todayAttendance as $att) {
                $employee = $att->user?->employee;
                $shift = $att->shift ?? $employee?->currentShift?->shift;
                $displayShiftTime = $shift ? $shift->getDisplayTimeRange() : 'N/A';

                fputcsv($file, [
                    $selectedDate,
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'Unknown',
                    $displayShiftTime,
                    $att->check_in ? $att->check_in->format('H:i') : '',
                    $att->check_out ? $att->check_out->format('H:i') : '',
                    $statusLabels[$att->status] ?? 'Unknown',
                    $att->notes ?? ''
                ]);
            }

            fclose($file);
        }, 200, $responseHeaders);
    }

    public function storeManual(Request $request)
    {
        $lateDeductionService = app(LateDeductionService::class);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'nullable|integer|in:1,2,3,4',
            'notes' => 'nullable|string|max:500',
        ]);

        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        $allowedEmployeeIds = $this->getAllowedEmployeeIds($currentUser);
        if ($allowedEmployeeIds !== null && !in_array((int)$validated['employee_id'], $allowedEmployeeIds)) {
            abort(403, 'Unauthorized action.');
        }

        $employee = Employee::with('user', 'shiftAssignments.shift')->findOrFail($validated['employee_id']);
        $user = $employee->user;

        // Auto-create or link user account if employee doesn't have one
        if (! $user) {
            // Prefer linking to an existing user with the same email to avoid duplicate key errors
            $found = null;
            if (! empty($employee->email)) {
                $found = User::where('email', $employee->email)->first();
            }

            if ($found) {
                $user = $found;
                // Link the existing user to this employee only if it's not already linked
                if (empty($user->employee_id)) {
                    $user->employee_id = $employee->id;
                    $user->save();
                }
            } else {
                $user = User::create([
                    'name' => $employee->full_name,
                    'employee_id' => $employee->id,
                    'email' => $employee->email ?? 'employee.' . $employee->id . '@system.local',
                    'username' => $employee->employee_code ?? ('emp_' . $employee->id),
                    'password' => bcrypt('default_password_' . $employee->id),
                ]);
            }
        }

        $attendanceDate = Carbon::parse($validated['attendance_date']);
        $shift = $employee->getActiveShiftForDate($attendanceDate);

        // If there's no assigned shift for the date, allow manual time-in as long as
        // the attendance date is not before the employee's hire date. Additionally,
        // if the employee has any shift assignment (past or future) whose
        // days_of_week includes the attendance weekday, use that shift to derive
        // status/late calculations even if its effective_from is different.
        $candidateShift = null;
        if (!$shift) {
            $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : null;

                if ($hireDate && $attendanceDate->lt($hireDate)) {
                    return back()->withErrors(['employee_id' => 'Cannot create attendance before the employee\'s hire date.'])->withInput();
                }

            $dayOfWeek = $attendanceDate->format('D');
            $assignments = $employee->shiftAssignments()->with('shift')->get();
            foreach ($assignments as $assignment) {
                if ($assignment->shift && is_array($assignment->shift->days_of_week)) {
                    if (in_array($dayOfWeek, $assignment->shift->days_of_week)) {
                        $candidateShift = $assignment->shift;
                        break;
                    }
                }
            }

                // No active shift — determine if there is any candidate assignment that
                // matches the attendance weekday. If none exist, disallow manual time-in
                // to keep attendance tied to a shift schedule.
                // No active shift but date is >= hire date — previously this allowed
                // manual time-ins; change behaviour to require a shift schedule or an
                // assignment that covers the weekday.

                if (! $shift && ! $candidateShift) {
                    return back()->withErrors(['employee_id' => 'No shift schedule was found for this employee on the selected date.'])->withInput();
                }
        }

        $checkInDateTime = Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_in']);

        $existingAttendance = Attendance::where('emp_id', $employee->id)
            ->where('attendance_date', $validated['attendance_date'])
            ->where('punch_type', 'in')
            ->first();

        $existingCheckOut = Attendance::where('emp_id', $employee->id)
            ->where('attendance_date', $validated['attendance_date'])
            ->where('punch_type', 'out')
            ->first();

        if ($existingAttendance && $existingAttendance->check_in) {
            $existingCheckIn = Carbon::parse($existingAttendance->check_in)->format('H:i');
            $incomingCheckIn = $checkInDateTime->format('H:i');

            $isCompletingOpenEntry = is_null($existingCheckOut)
                && !empty($validated['check_out'])
                && $existingCheckIn === $incomingCheckIn;

            $isExactDuplicate = $existingCheckIn === $incomingCheckIn
                && empty($validated['check_out'])
                && !is_null($existingCheckOut);

            if ($isExactDuplicate) {
                return back()->withErrors([
                    'employee_id' => 'A time in record already exists for this employee on the selected date and time.',
                ])->withInput();
            }

            if ($existingCheckIn === $incomingCheckIn && !$isCompletingOpenEntry && is_null($existingCheckOut)) {
                return back()->withErrors([
                    'employee_id' => 'This open time in already exists. Add a time out to complete it.',
                ])->withInput();
            }
        }

        // Auto-determine status from the assigned shift if not explicitly provided
        if (empty($validated['status'])) {
            if ($shift) {
                $validated['status'] = $shift->getAttendanceStatusForClockIn($checkInDateTime)['key'];
            } elseif ($candidateShift) {
                $validated['status'] = $candidateShift->getAttendanceStatusForClockIn($checkInDateTime)['key'];
            } else {
                // No shift available to calculate lateness — default to Present.
                $validated['status'] = 1;
            }
        }

        $checkOutDateTime = !empty($validated['check_out'])
            ? Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_out'])
            : null;

        $shiftForMidnight = $shift ?? $candidateShift;
        if ($checkOutDateTime && $shiftForMidnight && $checkOutDateTime->lt($checkInDateTime) && $shiftForMidnight->crosses_midnight) {
            $checkOutDateTime->addDay();
        }

        $shiftId = Schema::hasColumn('attendance', 'shift_id') && $shift ? $shift->id : null;

        $attendance = Attendance::upsertPunch(
            $employee->id,
            $validated['attendance_date'],
            'in',
            $checkInDateTime,
            $shiftId,
            (int) $validated['status']
        );

        if ($checkOutDateTime) {
            Attendance::upsertPunch(
                $employee->id,
                $validated['attendance_date'],
                'out',
                $checkOutDateTime,
                $shiftId,
                (int) $validated['status']
            );
        }

        if ($attendance) {
            $shiftForLate = $shift ?? $candidateShift;
            if ($shiftForLate) {
                $lateDeductionService->recordAttendanceLateDeduction(
                    $attendance->loadMissing('user.employee'),
                    $shiftForLate,
                    $checkInDateTime,
                    true
                );
            }
        }

        return redirect()->route('timekeeping.index')
            ->with('success', 'Attendance record saved successfully.');
    }

    public function shiftSchedule()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $allowedEmployeeIds = $this->getAllowedEmployeeIds($user);

        $employeesQuery = \App\Models\Employee::with(['department', 'currentShifts.shift']);
        if ($allowedEmployeeIds !== null) {
            $employeesQuery->whereIn('id', $allowedEmployeeIds);
        }
        $employees = $employeesQuery->get();
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('timekeeping.shift-schedule', compact('employees', 'departments'));
    }

    public function exportShiftSchedule(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->role !== 4) {
            abort(403, 'Unauthorized action. Exports are restricted to HR only.');
        }

        $request->validate([
            'q'             => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $allowedEmployeeIds = $this->getAllowedEmployeeIds($user);

        $employeesQuery = \App\Models\Employee::with(['department', 'currentShifts.shift']);
        if ($allowedEmployeeIds !== null) {
            $employeesQuery->whereIn('id', $allowedEmployeeIds);
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $employeesQuery->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('middle_name', 'like', "%{$q}%")
                    ->orWhere('employee_code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('department_id')) {
            $employeesQuery->where('department_id', $request->input('department_id'));
        }

        $employees = $employeesQuery->get();

        $filename = "shift_schedule_export_" . now()->format('Ymd_His') . ".csv";

        $responseHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($employees) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Employee Code',
                'Employee Name',
                'Department',
                'Schedules',
                'Working Days'
            ]);

            foreach ($employees as $employee) {
                $activeShifts = $employee->currentShifts ?? collect();
                if ($activeShifts->isEmpty() && $employee->currentShift) {
                    $activeShifts = collect([$employee->currentShift]);
                }

                $schedulesList = [];
                $workingDaysList = [];

                foreach ($activeShifts as $assignment) {
                    if ($assignment->shift) {
                        $shift = $assignment->shift;
                        
                        if ($shift->is_flexible && $shift->flexible_until_time) {
                            $timeStr = \Carbon\Carbon::parse($shift->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($shift->flexible_until_time)->format('g:i A') . ' to ' . \Carbon\Carbon::parse($shift->end_time)->format('g:i A');
                        } else {
                            $flex = $shift->is_flexible ? ' [Flex]' : '';
                            $timeStr = \Carbon\Carbon::parse($shift->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($shift->end_time)->format('h:i A') . $flex;
                        }
                        
                        $daysStr = implode(', ', array_map(function($d) { return substr($d, 0, 3); }, $shift->days_of_week ?? []));
                        $schedulesList[] = "{$timeStr} ({$daysStr})";

                        if (is_array($shift->days_of_week)) {
                            $workingDaysList = array_merge($workingDaysList, $shift->days_of_week);
                        }
                    }
                }

                $workingDaysList = array_unique($workingDaysList);
                $workingDaysStr = count($workingDaysList) > 0 ? implode(', ', $workingDaysList) : 'Not assigned';
                $schedulesStr = count($schedulesList) > 0 ? implode('; ', $schedulesList) : 'Not assigned';

                fputcsv($file, [
                    $employee->employee_code,
                    $employee->full_name,
                    $employee->department->name ?? 'Unassigned',
                    $schedulesStr,
                    $workingDaysStr
                ]);
            }

            fclose($file);
        }, 200, $responseHeaders);
    }

    public function saveShiftSchedule(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_time' => 'required',
            'end_time' => 'required',
            'break_minutes' => 'nullable|integer|min:0',
            'days' => 'array',
            'days.*' => 'string',
            'is_flexible' => 'boolean',
            'flexible_until_time' => 'nullable',
            'assignment_id' => 'nullable|integer|exists:shift_assignments,id',
        ]);

        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        $allowedEmployeeIds = $this->getAllowedEmployeeIds($currentUser);
        if ($allowedEmployeeIds !== null && !in_array((int)$validated['employee_id'], $allowedEmployeeIds)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $days = $validated['days'] ?? [];
        $breakMinutes = (int) ($validated['break_minutes'] ?? 60);
        $isFlexible = $validated['is_flexible'] ?? false;
        $flexibleUntilTime = !empty($validated['flexible_until_time']) ? date('H:i:s', strtotime($validated['flexible_until_time'])) : null;

        $start = date('H:i:s', strtotime($validated['start_time']));
        $end = date('H:i:s', strtotime($validated['end_time']));

        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);
        $crossesMidnight = $end < $start;

        if ($crossesMidnight) {
            $durationMinutes = $endCarbon->copy()->addDay()->diffInMinutes($startCarbon);
        } else {
            $durationMinutes = $endCarbon->diffInMinutes($startCarbon);
        }

        $isNightShift = ($startCarbon->hour >= 22 || $startCarbon->hour < 6);

        $shift = \App\Models\Shift::where('start_time', $start)
            ->where('end_time', $end)
            ->where('break_minutes', $breakMinutes)
            ->where('is_flexible', $isFlexible)
            ->where('flexible_until_time', $flexibleUntilTime)
            ->where('days_of_week', json_encode($days))
            ->first();

        if (!$shift) {
            $shift = \App\Models\Shift::create([
                'name' => 'Shift ' . date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end)),
                'start_time' => $start,
                'end_time' => $end,
                'break_minutes' => $breakMinutes,
                'is_night_shift' => $isNightShift,
                'crosses_midnight' => $crossesMidnight,
                'shift_duration_minutes' => $durationMinutes,
                'days_of_week' => $days,
                'is_active' => true,
                'is_flexible' => $isFlexible,
                'flexible_until_time' => $flexibleUntilTime,
                'flexible_hours' => 2, // Default 2 hours as per requirements
            ]);
        }

        if (!empty($validated['assignment_id'])) {
            $assignmentToEdit = \App\Models\ShiftAssignment::find($validated['assignment_id']);
            if ($assignmentToEdit && $assignmentToEdit->employee_id == $validated['employee_id'] && ((int) ($assignmentToEdit->status ?? 1) !== 13)) {
                // End the specific assignment being edited so it's fully replaced, avoiding overlap fragmentation
                $assignmentToEdit->update(['effective_to' => now()->yesterday()->toDateString()]);
            }
        }

        // Resolve overlaps with existing active shift assignments
        $activeAssignments = \App\Models\ShiftAssignment::with('shift')
            ->where('employee_id', $validated['employee_id'])
            ->whereNull('effective_to')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 13);
            })
            ->get();

        foreach ($activeAssignments as $assignment) {
            $existingShift = $assignment->shift;
            if (!$existingShift || !is_array($existingShift->days_of_week)) {
                continue;
            }

            $overlap = array_intersect($existingShift->days_of_week, $days);
            if (empty($overlap)) {
                continue;
            }

            $remainingDays = array_values(array_diff($existingShift->days_of_week, $days));

            if (empty($remainingDays)) {
                $assignment->update(['effective_to' => now()->yesterday()->toDateString()]);
            } else {
                $newRemainingShift = \App\Models\Shift::where('start_time', $existingShift->start_time)
                    ->where('end_time', $existingShift->end_time)
                    ->where('break_minutes', $existingShift->break_minutes)
                    ->where('is_flexible', $existingShift->is_flexible)
                    ->where('flexible_until_time', $existingShift->flexible_until_time)
                    ->where('days_of_week', json_encode($remainingDays))
                    ->first();

                if (!$newRemainingShift) {
                    $newRemainingShift = \App\Models\Shift::create([
                        'name' => 'Shift ' . date('g:i A', strtotime($existingShift->start_time)) . ' - ' . date('g:i A', strtotime($existingShift->end_time)),
                        'start_time' => $existingShift->start_time,
                        'end_time' => $existingShift->end_time,
                        'break_minutes' => $existingShift->break_minutes,
                        'is_night_shift' => $existingShift->is_night_shift,
                        'crosses_midnight' => $existingShift->crosses_midnight,
                        'shift_duration_minutes' => $existingShift->shift_duration_minutes,
                        'days_of_week' => $remainingDays,
                        'is_active' => true,
                        'is_flexible' => $existingShift->is_flexible,
                        'flexible_until_time' => $existingShift->flexible_until_time,
                        'flexible_hours' => $existingShift->flexible_hours,
                    ]);
                }

                $assignment->update(['shift_id' => $newRemainingShift->id]);
            }
        }

        $activeAssignment = \App\Models\ShiftAssignment::where('employee_id', $validated['employee_id'])
            ->where('shift_id', $shift->id)
            ->whereNull('effective_to')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 13);
            })
            ->first();

        if ($activeAssignment) {
            $activeAssignment->update([
                'effective_from' => now()->toDateString(),
                'status' => 1,
            ]);
        } else {
            \App\Models\ShiftAssignment::create([
                'employee_id' => $validated['employee_id'],
                'shift_id' => $shift->id,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'status' => 1,
            ]);
        }

        // Update any current/future attendance records to reflect the new shift schedule
        $employee = Employee::with('user')->find($validated['employee_id']);
        if ($employee && $employee->user) {
            $shiftService = app(\App\Services\ShiftService::class);
            $lateDeductionService = app(LateDeductionService::class);
            $todayStr = now()->toDateString();
            $attendances = Attendance::where('emp_id', $employee->id)
                ->where('punch_type', 'in')
                ->where('attendance_date', '>=', $todayStr)
                ->get();

            foreach ($attendances as $att) {
                $correctShift = $employee->getActiveShiftForDate($att->attendance_date);
                $correctShiftId = $correctShift ? $correctShift->id : null;

                if ($att->shift_id != $correctShiftId) {
                    $att->shift_id = $correctShiftId;

                    if ($att->check_in && $correctShift) {
                        $attendanceStatus = $shiftService->calculateAttendanceStatus($correctShift, $att->check_in, $att->check_out);
                        $att->status = $attendanceStatus['status'] === 'late' ? 2 : 1;
                    } else {
                        $att->status = 1;
                    }

                    $att->save();

                    if ($att->check_in && $correctShift) {
                        $lateDeductionService->recordAttendanceLateDeduction($att, $correctShift, $att->check_in, true);
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function softDeleteShiftSchedule(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        if (! $currentUser || (int) $currentUser->role !== 4) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'assignment_id' => 'required|integer|exists:shift_assignments,id',
        ]);

        $assignment = \App\Models\ShiftAssignment::with('employee.user')->find($validated['assignment_id']);
        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'Shift schedule not found.'], 404);
        }

        $assignment->update([
            'status' => 13,
            'effective_to' => now()->yesterday()->toDateString(),
        ]);

        $employee = $assignment->employee;
        if ($employee && $employee->user) {
            $shiftService = app(\App\Services\ShiftService::class);
            $lateDeductionService = app(LateDeductionService::class);
            $todayStr = now()->toDateString();
            $attendances = Attendance::where('emp_id', $employee->id)
                ->where('punch_type', 'in')
                ->where('attendance_date', '>=', $todayStr)
                ->get();

            foreach ($attendances as $att) {
                $correctShift = $employee->getActiveShiftForDate($att->attendance_date);
                $correctShiftId = $correctShift ? $correctShift->id : null;

                if ($att->shift_id != $correctShiftId) {
                    $att->shift_id = $correctShiftId;

                    if ($att->check_in && $correctShift) {
                        $attendanceStatus = $shiftService->calculateAttendanceStatus($correctShift, $att->check_in, $att->check_out);
                        $att->status = $attendanceStatus['status'] === 'late' ? 2 : 1;
                    } else {
                        $att->status = 1;
                    }

                    $att->save();

                    if ($att->check_in && $correctShift) {
                        $lateDeductionService->recordAttendanceLateDeduction($att, $correctShift, $att->check_in, true);
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function show(User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        $allowedEmployeeIds = $this->getAllowedEmployeeIds($currentUser);

        if ($allowedEmployeeIds !== null) {
            if (!$user->employee_id || !in_array((int) $user->employee_id, $allowedEmployeeIds)) {
                abort(403, 'Unauthorized action.');
            }
        }

        $attendances = Attendance::with(['shift', 'user.employee.currentShift.shift'])
            ->where('emp_id', $user->employee_id)
            ->where('punch_type', 'in')
            ->orderByDesc('attendance_date')
            ->paginate(30);

        return view('timekeeping.show', compact('user', 'attendances'));
    }
}
