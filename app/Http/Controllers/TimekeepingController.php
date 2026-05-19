<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
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

        return view('timekeeping.index', [
            'todayAttendance' => $todayAttendance,
            'attendanceStatusLabels' => $attendanceStatusLabels,
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'absentToday' => $absentToday,
            'activeAttendance' => $activeAttendance,
            'todayDate' => Carbon::now(),
            'recentAttendance' => $recentAttendance,
        ]);
    }
}
