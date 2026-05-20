<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = Carbon\Carbon::now()->toDateString();
$todayAttendance = App\Models\Attendance::with(['user.employee.currentShift.shift', 'shift'])
    ->where('attendance_date', $today)
    ->get();

foreach ($todayAttendance as $att) {
    $shift = $att->shift ?? $att->user?->employee?->currentShift?->shift;
    $workedHours = null;
    if ($att->check_in && $att->check_out) {
        $timeIn = \Carbon\Carbon::createFromFormat('H:i:s', $att->check_in->format('H:i:s'));
        $timeOut = \Carbon\Carbon::createFromFormat('H:i:s', $att->check_out->format('H:i:s'));

        if ($shift?->crosses_midnight && $timeOut->lt($timeIn)) {
            $timeOut->addDay();
        }

        $workedHours = round($timeOut->diffInMinutes($timeIn) / 60, 2);
    }
    echo "User: " . $att->user->name . " | Check In: " . $att->check_in . " | Check Out: " . $att->check_out . " | workedHours: " . var_export($workedHours, true) . "\n";
}
