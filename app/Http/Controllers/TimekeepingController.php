<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TimekeepingController extends Controller
{
    public function index()
    {
        $today = Carbon::now()->toDateString();

        $todayAttendance = Schema::hasTable('attendance')
            ? Attendance::with('user')
                ->where('attendance_date', $today)
                ->orderBy('check_in')
                ->get()
            : collect();

        $recentAttendance = Schema::hasTable('attendance')
            ? Attendance::with('user')
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

        $users = User::orderBy('name')->get();

        return view('timekeeping.index', [
            'todayAttendance' => $todayAttendance,
            'attendanceStatusLabels' => $attendanceStatusLabels,
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'absentToday' => $absentToday,
            'activeAttendance' => $activeAttendance,
            'todayDate' => Carbon::now(),
            'recentAttendance' => $recentAttendance,
            'users' => $users,
        ]);
    }

    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'user_id'         => 'required|exists:users,id',
            'attendance_date' => 'required|date',
            'check_in'        => 'required|date_format:H:i',
            'check_out'       => 'nullable|date_format:H:i|after:check_in',
            'status'          => 'nullable|integer|in:1,2,3,4',
            'notes'           => 'nullable|string|max:500',
        ]);

        $checkInDateTime = Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_in']);

        // Auto-determine status if not explicitly provided
        if (empty($validated['status'])) {
            $cutoff  = Carbon::parse($validated['attendance_date'] . ' 08:00');
            $validated['status'] = $checkInDateTime->gt($cutoff) ? 2 : 1; // 2=late, 1=present
        }

        $checkOutDateTime = !empty($validated['check_out'])
            ? Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_out'])
            : null;

        Attendance::updateOrCreate(
            [
                'user_id'         => $validated['user_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'check_in'  => $checkInDateTime,
                'check_out' => $checkOutDateTime,
                'status'    => $validated['status'],
                'notes'     => $validated['notes'] ?? null,
            ]
        );

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
