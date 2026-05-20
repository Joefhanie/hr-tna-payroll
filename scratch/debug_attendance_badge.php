<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = Carbon\Carbon::now()->toDateString();
$todayAttendance = App\Models\Attendance::with(['user.employee.currentShift.shift', 'shift'])
    ->where('attendance_date', $today)
    ->orderBy('check_in')
    ->get();

foreach ($todayAttendance as $att) {
    $shift = $att->shift ?? $att->user?->employee?->currentShift?->shift;
    if ($shift) {
        echo "Attendance user: " . $att->user->name . " | Shift ID: " . $shift->id . " | workingHours: " . $shift->getWorkingHoursPerDay() . " | <=4.0: " . ($shift->getWorkingHoursPerDay() <= 4.0 ? 'YES' : 'NO') . "\n";
    }
}
