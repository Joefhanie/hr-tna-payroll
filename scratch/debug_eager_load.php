<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;

$employees = Employee::with(['department', 'currentShifts.shift'])->get();
foreach ($employees as $emp) {
    if ($emp->employee_code === 'JD001') {
        echo "Employee: " . $emp->full_name . "\n";
        echo "Active shifts count: " . $emp->currentShifts->count() . "\n";
        foreach ($emp->currentShifts as $cs) {
            echo "Assignment ID: " . $cs->id . ", Shift ID: " . $cs->shift_id . "\n";
            if ($cs->shift) {
                echo "  Shift days: " . json_encode($cs->shift->days_of_week) . ", time: " . $cs->shift->start_time . "\n";
            }
        }
    }
}
