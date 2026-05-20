<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\ShiftAssignment;

$employee = Employee::where('employee_code', 'JD001')->first();
if (!$employee) {
    echo "Employee not found.\n";
    exit;
}

echo "Employee: " . $employee->full_name . " (ID: " . $employee->id . ")\n";

$assignments = ShiftAssignment::with('shift')
    ->where('employee_id', $employee->id)
    ->get();

echo "Total assignments in database: " . $assignments->count() . "\n";
foreach ($assignments as $a) {
    echo "ID: " . $a->id . ", Shift ID: " . $a->shift_id . ", From: " . $a->effective_from . ", To: " . ($a->effective_to ?? 'null') . "\n";
    if ($a->shift) {
        echo "  Shift: " . $a->shift->name . ", Days: " . json_encode($a->shift->days_of_week) . "\n";
    }
}
