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
    public function index(Request $request)
    {
        $selectedDate = $request->query('date', Carbon::now()->toDateString());
        $selectedDateCarbon = Carbon::parse($selectedDate);

        $employees = Schema::hasTable('employees')
            ? Employee::with(['user', 'currentShift.shift', 'currentShifts.shift'])->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name')->get()
            : collect();

        $todayAttendance = Schema::hasTable('attendance')
            ? Attendance::with(['user.employee.currentShift.shift', 'shift'])
                ->where('attendance_date', $selectedDate)
                ->orderBy('check_in')
                ->get()
            : collect();

        $calendarDataRecords = Schema::hasTable('attendance')
            ? Attendance::with(['user.employee.currentShift.shift', 'shift'])
                ->whereBetween('attendance_date', [
                    $selectedDateCarbon->copy()->startOfMonth()->toDateString(),
                    $selectedDateCarbon->copy()->endOfMonth()->toDateString()
                ])
                ->orderBy('check_in')
                ->get()
            : collect();

        // Helper function to insert virtual "Shift Not Started" records
        $addVirtualShiftNotStarted = function ($attendanceCollection, $employees, $startDate, $endDate = null) {
            $endDate = $endDate ?: $startDate;
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            $existingMap = [];
            foreach ($attendanceCollection as $att) {
                $dateStr = $att->attendance_date->toDateString();
                $existingMap[$dateStr][$att->user_id] = true;
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
                        
                        if ($hasNotStarted) {
                            $virtual = new Attendance([
                                'user_id' => $user->id,
                                'shift_id' => $dayShift->id,
                                'attendance_date' => $cursor->copy(),
                                'status' => 5,
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

        // Add virtual records to today's list and the calendar data
        $todayAttendance = $addVirtualShiftNotStarted($todayAttendance, $employees, $selectedDate);
        $calendarDataRecords = $addVirtualShiftNotStarted(
            $calendarDataRecords, 
            $employees, 
            $selectedDateCarbon->copy()->startOfMonth()->toDateString(), 
            $selectedDateCarbon->copy()->endOfMonth()->toDateString()
        );

        $calendarData = $calendarDataRecords->groupBy(function ($attendance) {
            return $attendance->attendance_date->toDateString();
        });

        $recentAttendance = Schema::hasTable('attendance')
            ? Attendance::with(['user.employee.currentShift.shift', 'shift'])
                ->whereIn('user_id', $todayAttendance->pluck('user_id')->unique())
                ->where('attendance_date', '>=', Carbon::now()->subDays(30)->toDateString())
                ->orderByDesc('attendance_date')
                ->orderByDesc('check_in')
                ->get()
                ->unique(fn ($attendance) => $attendance->user_id.'|'.$attendance->attendance_date->toDateString())
                ->groupBy('user_id')
            : collect();

        $attendanceStatusLabels = [
            1 => 'Present',
            2 => 'Late',
            3 => 'Absent',
            4 => 'Excused',
            5 => 'Shift Not Started',
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'excused' => 'Excused',
            'not_started' => 'Shift Not Started',
        ];

        $normalizeStatus = function ($status) {
            if (is_numeric($status)) {
                return (int) $status;
            }

            return strtolower((string) $status);
        };

        $statusCounts = $todayAttendance->countBy(function ($attendance) use ($normalizeStatus) {
            return $normalizeStatus($attendance->status);
        });

        $presentToday = (int) ($statusCounts[1] ?? $statusCounts['present'] ?? 0);
        $lateToday = (int) ($statusCounts[2] ?? $statusCounts['late'] ?? 0);
        $absentToday = (int) ($statusCounts[3] ?? $statusCounts['absent'] ?? 0);

        $activeAttendance = $todayAttendance->first(function ($attendance) use ($normalizeStatus) {
            $status = $normalizeStatus($attendance->status);

            return in_array($status, [1, 'present', 2, 'late'], true) && $attendance->check_in;
        }) ?? $todayAttendance->first();

        $openAttendanceMap = Schema::hasTable('attendance')
            ? Attendance::whereNull('check_out')
                ->get()
                ->mapWithKeys(function ($attendance) {
                    return [
                        $attendance->user_id . '|' . $attendance->attendance_date->toDateString() => $attendance->check_in?->format('H:i'),
                    ];
                })
                ->all()
            : [];

        $selectedDateCarbon = Carbon::parse($selectedDate);

        return view('timekeeping.index', [
            'todayAttendance' => $todayAttendance,
            'attendanceStatusLabels' => $attendanceStatusLabels,
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'absentToday' => $absentToday,
            'activeAttendance' => $activeAttendance,
            'todayDate' => Carbon::now(),
            'selectedDate' => $selectedDate,
            'recentAttendance' => $recentAttendance,
            'users' => $employees,
            'openAttendanceMap' => $openAttendanceMap,
            'calendarData' => $calendarData,
        ]);
    }

    public function storeManual(Request $request)
    {
        $lateDeductionService = app(LateDeductionService::class);

        $validated = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in'        => 'required|date_format:H:i',
            'check_out'       => 'nullable|date_format:H:i',
            'status'          => 'nullable|integer|in:1,2,3,4',
            'notes'           => 'nullable|string|max:500',
        ]);

        $employee = Employee::with('user', 'shiftAssignments.shift')->findOrFail($validated['employee_id']);
        $user = $employee->user;

        // Auto-create user account if employee doesn't have one
        if (!$user) {
            $user = User::create([
                'name' => $employee->full_name,
                'employee_id' => $employee->id,
                'email' => $employee->email ?? 'employee.' . $employee->id . '@system.local',
                'username' => $employee->employee_code ?? ('emp_' . $employee->id),
                'password' => bcrypt('default_password_' . $employee->id),
            ]);
        }

        $attendanceDate = Carbon::parse($validated['attendance_date']);
        $shift = $employee->getActiveShiftForDate($attendanceDate);

        if (!$shift) {
            return back()->withErrors(['employee_id' => 'No active shift schedule was found for this employee on the selected date.'])->withInput();
        }

        $checkInDateTime = Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_in']);

        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('attendance_date', $validated['attendance_date'])
            ->first();

        if ($existingAttendance && $existingAttendance->check_in) {
            $existingCheckIn = Carbon::parse($existingAttendance->check_in)->format('H:i');
            $incomingCheckIn = $checkInDateTime->format('H:i');

            $isCompletingOpenEntry = is_null($existingAttendance->check_out)
                && !empty($validated['check_out'])
                && $existingCheckIn === $incomingCheckIn;

            $isExactDuplicate = $existingCheckIn === $incomingCheckIn
                && empty($validated['check_out'])
                && !is_null($existingAttendance->check_out);

            if ($isExactDuplicate) {
                return back()->withErrors([
                    'employee_id' => 'A time in record already exists for this employee on the selected date and time.',
                ])->withInput();
            }

            if ($existingCheckIn === $incomingCheckIn && !$isCompletingOpenEntry && is_null($existingAttendance->check_out)) {
                return back()->withErrors([
                    'employee_id' => 'This open time in already exists. Add a time out to complete it.',
                ])->withInput();
            }
        }

        // Auto-determine status from the assigned shift if not explicitly provided
        if (empty($validated['status'])) {
            $validated['status'] = $shift->getAttendanceStatusForClockIn($checkInDateTime)['key'];
        }

        $checkOutDateTime = !empty($validated['check_out'])
            ? Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_out'])
            : null;

        if ($checkOutDateTime && $checkOutDateTime->lt($checkInDateTime) && $shift->crosses_midnight) {
            $checkOutDateTime->addDay();
        }

        $attendanceData = [
            'check_in'  => $checkInDateTime,
            'check_out' => $checkOutDateTime,
            'status'    => $validated['status'],
            'notes'     => $validated['notes'] ?? null,
        ];

        if (Schema::hasColumn('attendance', 'shift_id')) {
            $attendanceData['shift_id'] = $shift->id;
        }

        $attendance = Attendance::updateOrCreate(
            [
                'user_id'         => $user->id,
                'attendance_date' => $validated['attendance_date'],
            ],
            $attendanceData
        );

        if ($attendance) {
            $lateDeductionService->recordAttendanceLateDeduction(
                $attendance->loadMissing('user.employee'),
                $shift,
                $checkInDateTime,
                true
            );
        }

        return redirect()->route('timekeeping.index')
            ->with('success', 'Attendance record saved successfully.');
    }

    public function shiftSchedule()
    {
        $employees = \App\Models\Employee::with(['department', 'currentShifts.shift'])->get();
        return view('timekeeping.shift-schedule', compact('employees'));
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
            if ($assignmentToEdit && $assignmentToEdit->employee_id == $validated['employee_id']) {
                // End the specific assignment being edited so it's fully replaced, avoiding overlap fragmentation
                $assignmentToEdit->update(['effective_to' => now()->yesterday()->toDateString()]);
            }
        }

        // Resolve overlaps with existing active shift assignments
        $activeAssignments = \App\Models\ShiftAssignment::with('shift')
            ->where('employee_id', $validated['employee_id'])
            ->whereNull('effective_to')
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

        \App\Models\ShiftAssignment::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'shift_id' => $shift->id, 'effective_to' => null],
            ['effective_from' => now()->toDateString()]
        );

        return response()->json(['success' => true]);
    }

    public function show(User $user)
    {
        $attendances = Attendance::with(['shift', 'user.employee.currentShift.shift'])
            ->where('user_id', $user->id)
            ->orderByDesc('attendance_date')
            ->paginate(30);

        return view('timekeeping.show', compact('user', 'attendances'));
    }
}
