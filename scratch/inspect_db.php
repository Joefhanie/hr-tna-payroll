<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Attendance;

$attendances = Attendance::with(['user.employee'])->get();
$mismatches = [];

foreach ($attendances as $att) {
    $employee = $att->user?->employee;
    if (!$employee) continue;

    $correctShift = $employee->getActiveShiftForDate($att->attendance_date);
    $correctShiftId = $correctShift ? $correctShift->id : null;

    if ($att->shift_id != $correctShiftId) {
        $mismatches[] = [
            'id' => $att->id,
            'employee' => $employee->full_name,
            'date' => $att->attendance_date->toDateString(),
            'current_shift_id' => $att->shift_id,
            'current_shift_name' => $att->shift ? $att->shift->name : 'None',
            'correct_shift_id' => $correctShiftId,
            'correct_shift_name' => $correctShift ? $correctShift->name : 'None',
        ];
    }
}

echo "Found " . count($mismatches) . " mismatches:\n";
foreach ($mismatches as $m) {
    echo "ID: {$m['id']} | Emp: {$m['employee']} | Date: {$m['date']} | Has: {$m['current_shift_name']} (ID: {$m['current_shift_id']}) | Should be: {$m['correct_shift_name']} (ID: {$m['correct_shift_id']})\n";
}
