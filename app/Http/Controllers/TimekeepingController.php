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
    public function index()
    {
        $today = Carbon::now()->toDateString();

        $todayAttendance = Schema::hasTable('attendance')
            ? Attendance::with(['user.employee.currentShift.shift', 'shift'])
                ->where('attendance_date', $today)
                ->orderBy('check_in')
                ->get()
            : collect();

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
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'excused' => 'Excused',
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

        $employees = Schema::hasTable('employees')
            ? Employee::orderBy('first_name')->orderBy('middle_name')->orderBy('last_name')->get()
            : collect();

        $calendarData = Schema::hasTable('attendance')
            ? Attendance::with(['user.employee.currentShift.shift', 'shift'])
                ->whereBetween('attendance_date', [
                    Carbon::now()->startOfMonth()->toDateString(),
                    Carbon::now()->endOfMonth()->toDateString()
                ])
                ->orderBy('check_in')
                ->get()
                ->groupBy(function ($attendance) {
                    return $attendance->attendance_date->toDateString();
                })
            : collect();

        return view('timekeeping.index', [
            'todayAttendance' => $todayAttendance,
            'attendanceStatusLabels' => $attendanceStatusLabels,
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'absentToday' => $absentToday,
            'activeAttendance' => $activeAttendance,
            'todayDate' => Carbon::now(),
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
            $validated['status'] = $shift->getAttendanceStatusForClockIn($checkInDateTime, 10)['key'];
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
        $employees = \App\Models\Employee::with(['department', 'currentShift.shift'])->get();
        return view('timekeeping.shift-schedule', compact('employees'));
    }

    public function saveShiftSchedule(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_time' => 'required',
            'end_time' => 'required',
            'days' => 'array',
            'days.*' => 'string',
        ]);

        $days = $validated['days'] ?? [];

        $start = date('H:i:s', strtotime($validated['start_time']));
        $end = date('H:i:s', strtotime($validated['end_time']));

        $shift = \App\Models\Shift::where('start_time', $start)
            ->where('end_time', $end)
            ->where('days_of_week', json_encode($days))
            ->first();

        if (!$shift) {
            $shift = \App\Models\Shift::create([
                'name' => 'Shift ' . $start . '-' . $end,
                'start_time' => $start,
                'end_time' => $end,
                'break_minutes' => 60,
                'is_night_shift' => false,
                'days_of_week' => $days,
                'is_active' => true,
            ]);
        }

        \App\Models\ShiftAssignment::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'effective_to' => null],
            ['shift_id' => $shift->id, 'effective_from' => now()->toDateString()]
        );

        return response()->json(['success' => true]);
    }

    public function show(User $user)
    {
        $attendances = Attendance::where('user_id', $user->id)
            ->orderByDesc('attendance_date')
            ->paginate(30);

        return view('timekeeping.show', compact('user', 'attendances'));
    }
}
