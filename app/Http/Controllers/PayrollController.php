<?php

namespace App\Http\Controllers;

use App\Models\PayRun;
use App\Models\Employee;
use App\Models\EmployeePlotting;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    /**
     * Display the payroll dashboard.
     */
    public function index(): View
    {
        $currentYear = now()->year;

        // Get all pay runs for the current year, excluding deleted ones (status 13)
        $payRuns = PayRun::whereYear('period_start', $currentYear)
            ->where('status', '!=', 13)
            ->orderBy('period_end', 'desc')
            ->get();

        // Calculate YTD totals
        $ytdGross = $payRuns->sum(function ($payRun) {
            return $payRun->payslips()->sum('gross_pay');
        });

        $ytdNet = $payRuns->sum(function ($payRun) {
            return $payRun->payslips()->sum('net_pay');
        });

        // Get current month statutory (government contributions)
        $currentMonth = now()->month;
        $statutoryAmount = PayRun::whereYear('period_start', $currentYear)
            ->whereMonth('period_end', $currentMonth)
            ->where('status', '!=', 13)
            ->with('payslips.governmentContributions')
            ->get()
            ->flatMap(function ($payRun) {
                return $payRun->payslips;
            })
            ->flatMap(function ($payslip) {
                return $payslip->governmentContributions;
            })
            ->sum(function ($contribution) {
                return $contribution->employee_share + $contribution->employer_share;
            });

        return view('payroll.index', compact('payRuns', 'ytdGross', 'ytdNet', 'statutoryAmount'));
    }

    /**
     * Display the spreadsheet-style payment plotting grid.
     */
    public function plottingPayment(): View
    {
        $dates = $this->fieldRecordDates();

        $scannedEmployeeCodes = DB::table('field_records')
            ->whereIn('Date', array_keys($dates))
            ->distinct()
            ->orderBy('empid')
            ->pluck('empid')
            ->all();

        // Build the plotting grid from scanned employees only.
        $employees = Employee::whereIn('employee_code', $scannedEmployeeCodes)
            ->whereNull('termination_date')
            ->with(['user', 'manager'])
            ->orderBy('first_name')
            ->get();

        $plottings = EmployeePlotting::whereIn('date', array_keys($dates))->get();
        $plottingMap = [];
        foreach ($plottings as $p) {
            $plottingMap[$p->employee_id][Carbon::parse($p->date)->format('Y-m-d')] = $p;
        }

        [$fieldRecordMap, $fieldSupervisorMap] = $this->fieldRecordMaps(array_keys($dates));

        $employeeMap = $employees->keyBy('id');
        $employeeCodeMap = $employees->keyBy('employee_code');

        $gridData = [];
        foreach ($employees as $employee) {
            $row = [
                'employee' => $employee,
                'days' => []
            ];

            foreach (array_keys($dates) as $date) {
                $plotting = $plottingMap[$employee->id][$date] ?? null;
                $amount = $plotting ? $plotting->amount : 0.00;
                $fieldRecord = $fieldRecordMap[$employee->employee_code][$date] ?? null;
                $fieldSupervisor = $fieldRecord ? ($fieldSupervisorMap[$fieldRecord['sup_id']][$date] ?? null) : null;
                $supervisorCode = $fieldRecord['sup_id'] ?? ($employee->manager?->employee_code ?? null);
                
                // Location resolution logic
                $isSupervisor = $employee->user && $employee->user->role === 2;
                $supervisor = $supervisorCode ? ($employeeCodeMap[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;

                if ($isSupervisor) {
                    $location = $fieldRecord['location'] ?? ($fieldSupervisor['location'] ?? 'General');
                    $svName = 'None';
                } else {
                    if ($fieldRecord && !empty($fieldRecord['location'])) {
                        $location = $fieldRecord['location'];
                    } elseif ($fieldSupervisor && !empty($fieldSupervisor['location'])) {
                        $location = $fieldSupervisor['location'];
                    } else {
                        $dailySupervisorId = $employee->manager_id;
                        $dailySupervisor = $dailySupervisorId ? ($employeeMap[$dailySupervisorId] ?? Employee::find($dailySupervisorId)) : null;
                        $location = ($dailySupervisor && isset($fieldSupervisorMap[$dailySupervisor->employee_code][$date]))
                            ? $fieldSupervisorMap[$dailySupervisor->employee_code][$date]['location']
                            : 'General';
                    }

                    $svName = $supervisor ? ($supervisor->first_name . ' ' . $supervisor->last_name) : 'None';
                }

                $row['days'][$date] = [
                    'amount' => $amount,
                    'location' => $location,
                    'supervisor_name' => $svName,
                    'supervisor_code' => $supervisorCode,
                    'supervisor_note' => $fieldRecord['notes'] ?? ($fieldSupervisor['notes'] ?? null)
                ];
            }

            $gridData[] = $row;
        }

        return view('payroll.plotting-payment', compact('dates', 'gridData'));
    }

    /**
     * Save the entire payment plotting grid.
     */
    public function savePlottingPayment(Request $request)
    {
        $entries = $request->input('entries', []);

        foreach ($entries as $employeeId => $dates) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                continue;
            }

            foreach ($dates as $date => $amount) {
                $cleanAmount = (float) str_replace([',', '$', ' '], '', $amount);

                $plotting = EmployeePlotting::where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->first();

                if ($plotting) {
                    $plotting->update([
                        'amount' => $cleanAmount
                    ]);
                } else {
                    $isSupervisor = $employee->user && $employee->user->role === 2;
                    $supervisorId = null;
                    $location = 'General';
                    $locationMap = $this->fieldRecordLocationMap([$date]);

                    if ($isSupervisor) {
                        $location = $locationMap[$employee->employee_code][$date] ?? 'General';
                    } else {
                        if ($employee->manager_id) {
                            $manager = $employee->manager;
                            $isManagerSupervisor = $manager && $manager->user && $manager->user->role === 2;
                            if ($isManagerSupervisor) {
                                $supervisorId = $employee->manager_id;
                                $location = $locationMap[$manager->employee_code][$date] ?? 'General';
                            }
                        }
                    }

                    EmployeePlotting::create([
                        'employee_id' => $employee->id,
                        'date' => $date,
                        'supervisor_id' => $supervisorId,
                        'amount' => $cleanAmount
                    ]);
                }
            }
        }

        return redirect()->route('payroll.plotting-payment')->with('status', 'Plotting payments updated successfully.');
    }

    /**
     * Show plotting details for a single employee.
     */
    public function showPlottingEmployee(Employee $employee): View
    {
        $dates = $this->fieldRecordDates();

        $plottings = EmployeePlotting::where('employee_id', $employee->id)
            ->whereIn('date', array_keys($dates))
            ->get()
            ->keyBy(function($p) {
                return Carbon::parse($p->date)->format('Y-m-d');
            });

        [$fieldRecordMap, $fieldSupervisorMap] = $this->fieldRecordMaps(array_keys($dates));
        $employeeCodeMap = Employee::whereNull('termination_date')->get()->keyBy('employee_code');

        $weekData = [];
        foreach ($dates as $dateString => $dateLabel) {
            $plotting = $plottings->get($dateString);
            $amount = $plotting ? $plotting->amount : 0.00;
            $fieldRecord = $fieldRecordMap[$employee->employee_code][$dateString] ?? null;
            $fieldSupervisor = $fieldRecord ? ($fieldSupervisorMap[$fieldRecord['sup_id']][$dateString] ?? null) : null;
            $supervisorCode = $fieldRecord['sup_id'] ?? ($employee->manager?->employee_code ?? null);
            $supervisor = $supervisorCode ? ($employeeCodeMap[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;

            // Location & Supervisor name resolution
            $location = 'General';
            $supervisorName = 'None';

            $isSupervisor = $employee->user && $employee->user->role === 2;

            if ($isSupervisor) {
                $location = $fieldRecord['location'] ?? ($fieldSupervisor['location'] ?? 'General');
            } else {
                $dailySupervisorId = $employee->manager_id;
                
                if ($fieldRecord && !empty($fieldRecord['location'])) {
                    $location = $fieldRecord['location'];
                } elseif ($fieldSupervisor && !empty($fieldSupervisor['location'])) {
                    $location = $fieldSupervisor['location'];
                } else {
                    if ($dailySupervisorId) {
                        $fallbackSupervisor = $employee->manager?->employee_code
                            ? ($employeeCodeMap[$employee->manager->employee_code] ?? Employee::find($dailySupervisorId))
                            : Employee::find($dailySupervisorId);
                        if ($fallbackSupervisor) {
                            $supervisorName = $fallbackSupervisor->first_name . ' ' . $fallbackSupervisor->last_name;
                            $location = $fieldSupervisorMap[$fallbackSupervisor->employee_code][$dateString]['location'] ?? 'General';
                        }
                    }
                }

                if ($supervisor) {
                    $supervisorName = $supervisor->first_name . ' ' . $supervisor->last_name;
                }
            }

            $weekData[] = [
                'date_string' => $dateString,
                'date' => $dateLabel,
                'workplace' => $location,
                'supervisor' => $supervisorName,
                'supervisor_code' => $supervisorCode,
                'supervisor_note' => $fieldRecord['notes'] ?? ($fieldSupervisor['notes'] ?? null),
                'amount' => $amount
            ];
        }

        return view('payroll.per-employee', compact('employee', 'weekData'));
    }

    /**
     * Save plotting details for a single employee.
     */
    public function savePlottingEmployee(Request $request, Employee $employee)
    {
        $entries = $request->input('entries', []);
        $locationMap = $this->fieldRecordLocationMap(array_keys($entries));

        foreach ($entries as $date => $amount) {
            $cleanAmount = (float) str_replace([',', '$', ' '], '', $amount);

            $plotting = EmployeePlotting::where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();

            if ($plotting) {
                $plotting->update([
                    'amount' => $cleanAmount
                ]);
            } else {
                $isSupervisor = $employee->user && $employee->user->role === 2;
                $supervisorId = null;
                $location = 'General';

                if ($isSupervisor) {
                    $location = $locationMap[$employee->employee_code][$date] ?? 'General';
                } else {
                    if ($employee->manager_id) {
                        $manager = $employee->manager;
                        $isManagerSupervisor = $manager && $manager->user && $manager->user->role === 2;
                        if ($isManagerSupervisor) {
                            $supervisorId = $employee->manager_id;
                            $location = $locationMap[$manager->employee_code][$date] ?? 'General';
                        }
                    }
                }

                EmployeePlotting::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'supervisor_id' => $supervisorId,
                    'amount' => $cleanAmount
                ]);
            }
        }

        return redirect()->route('payroll.plotting-payment')->with('status', "Plotting payments for {$employee->first_name} saved successfully.");
    }

    public function showWorkLocationDetails(string $date, string $workplace): View
    {
        $workplaceName = urldecode($workplace);

        // Get all employees (SVs and regular)
        $allEmployees = Employee::with(['user', 'manager'])->get();
        $employeeByCode = $allEmployees->keyBy('employee_code');

        $employeeData = [];

        // For location resolution
        [$fieldRecordMap, $fieldSupervisorMap] = $this->fieldRecordMaps([$date]);

        foreach ($allEmployees as $emp) {
            $isSupervisor = $emp->user && $emp->user->role === 2;
            
            // Get their plotting for this day
            $plotting = EmployeePlotting::where('employee_id', $emp->id)
                ->where('date', $date)
                ->first();

            $fieldRecord = $fieldRecordMap[$emp->employee_code][$date] ?? null;
            $fieldSupervisor = $fieldRecord ? ($fieldSupervisorMap[$fieldRecord['sup_id']][$date] ?? null) : null;
            $supervisorCode = $fieldRecord['sup_id'] ?? ($emp->manager?->employee_code ?? null);
            $supervisor = $supervisorCode ? ($employeeByCode[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;

            // Resolve daily location
            $loc = 'General';
            if ($isSupervisor) {
                $loc = $fieldRecord['location'] ?? ($fieldSupervisor['location'] ?? 'General');
            } else {
                if ($fieldRecord && !empty($fieldRecord['location'])) {
                    $loc = $fieldRecord['location'];
                } elseif ($fieldSupervisor && !empty($fieldSupervisor['location'])) {
                    $loc = $fieldSupervisor['location'];
                } else {
                    $dailySupervisorId = $emp->manager_id;
                    $dailySupervisor = $dailySupervisorId ? Employee::find($dailySupervisorId) : null;
                    $loc = ($dailySupervisor && isset($fieldSupervisorMap[$dailySupervisor->employee_code][$date]))
                        ? $fieldSupervisorMap[$dailySupervisor->employee_code][$date]['location']
                        : 'General';
                }
            }

            if ($loc === $workplaceName) {
                // Determine supervisor name
                $supervisorName = 'None';
                if ($supervisor) {
                    $supervisorName = $supervisor->first_name . ' ' . $supervisor->last_name;
                } elseif (!$isSupervisor) {
                    $dailySupervisorId = $emp->manager_id;
                    if ($dailySupervisorId) {
                        $sv = Employee::find($dailySupervisorId);
                        if ($sv) {
                            $supervisorName = $sv->first_name . ' ' . $sv->last_name;
                        }
                    }
                }

                $employeeData[] = [
                    'id' => $emp->id,
                    'name' => $emp->first_name . ' ' . $emp->last_name,
                    'supervisor' => $supervisorName,
                    'supervisor_code' => $supervisorCode,
                    'amount' => $plotting ? $plotting->amount : 0.00
                ];
            }
        }

        return view('payroll.work-location-details', compact('date', 'workplaceName', 'employeeData'));
    }

    /**
     * Build a lookup of work locations from field records keyed by employee code and date.
     */
    private function fieldRecordDates(): array
    {
        $dates = DB::table('field_records')
            ->whereNotNull('Date')
            ->distinct()
            ->orderBy('Date')
            ->pluck('Date')
            ->all();

        if (empty($dates)) {
            $dates = [now()->toDateString()];
        }

        return collect($dates)->mapWithKeys(function ($date) {
            return [$date => Carbon::parse($date)->format('M d')];
        })->all();
    }

    /**
     * Build lookups of field records keyed by employee code and supervisor code.
     */
    private function fieldRecordMaps(array $dates): array
    {
        $records = DB::table('field_records')
            ->select('empid', 'sup_id', 'Date', 'location', 'notes', 'time', 'id')
            ->whereIn('Date', $dates)
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        $employeeMap = [];
        $supervisorMap = [];
        foreach ($records as $record) {
            $payload = [
                'sup_id' => $record->sup_id,
                'location' => $record->location,
                'notes' => $record->notes,
            ];

            $employeeMap[$record->empid][$record->Date] = $payload;
            $supervisorMap[$record->sup_id][$record->Date] = $payload;
        }

        return [$employeeMap, $supervisorMap];
    }

    /**
     * Compatibility wrapper for call sites that only need employee-date locations.
     */
    private function fieldRecordLocationMap(array $dates): array
    {
        [$employeeMap] = $this->fieldRecordMaps($dates);
        $locationMap = [];

        foreach ($employeeMap as $empCode => $dateMap) {
            foreach ($dateMap as $date => $payload) {
                $locationMap[$empCode][$date] = $payload['location'] ?? null;
            }
        }

        return $locationMap;
    }

    public function showPerDateDetails(string $date): View
    {
        $dateFormatted = urldecode($date);

        // Sample employee data for the date
        $employeeData = [
            ['name' => 'Kenneth', 'workplace' => 'Manila Zoo', 'supervisor' => 'Andre', 'amount' => ''],
            ['name' => 'Alfren', 'workplace' => 'Manila Zoo', 'supervisor' => 'Andre', 'amount' => ''],
            ['name' => 'Jano', 'workplace' => 'Manila Zoo', 'supervisor' => 'Jim', 'amount' => ''],
            ['name' => 'KJ', 'workplace' => 'Manila Zoo', 'supervisor' => 'Jim', 'amount' => ''],
            ['name' => 'Jim', 'workplace' => 'Manila Zoo', 'supervisor' => 'Andrei', 'amount' => ''],
            ['name' => 'Andrei', 'workplace' => 'Manila Zoo', 'supervisor' => 'Andrei', 'amount' => ''],
        ];

        return view('payroll.per-date', compact('dateFormatted', 'employeeData'));
    }

    /**
     * Show form to create a new pay run.
     */
    public function create(): View
    {
        $employees = \App\Models\Employee::whereNull('termination_date')->orderBy('last_name')->get();
        return view('payroll.create', compact('employees'));
    }

    /**
     * Store the new pay run based on user input.
     */
    public function store(Request $request, PayrollService $payrollService)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);

        // PayRun Status: 1=Draft, 2=Processing, 3=Completed, 4=Cancelled
        $payRun = PayRun::create([
            'name' => $periodStart->format('M d') . ' - ' . $periodEnd->format('M d, Y'),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'pay_date' => $periodEnd->toDateString(),
            'frequency' => 4,  // Default to Monthly or customizable later
            'status' => 2,     // Processing / Draft Review
            'created_by' => $request->user()->id ?? null,
        ]);

        $payrollService->generateDraftPayRun($payRun, $validated['employee_ids']);

        return redirect()->route('payroll.show', $payRun)->with('status', 'Draft pay run created. Please review the breakdown below before finalizing.');
    }

    /**
     * Finalize the pay run after preview.
     */
    public function finalize(PayRun $payRun, PayrollService $payrollService)
    {
        if ($payRun->status == 3) {
            return back()->with('error', 'This pay run is already finalized.');
        }

        $payrollService->finalizePayRun($payRun);

        return redirect()->route('payroll.show', $payRun)->with('status', 'Pay run finalized successfully.');
    }

    /**
     * Show payroll details with payslips.
     */
    public function show(PayRun $payRun): View
    {
        $payRun->load('payslips.employee.salaryRecords', 'payslips.lineItems');
        $statusLabels = [1 => 'Draft', 2 => 'Processing', 3 => 'Completed', 4 => 'Cancelled'];

        return view('payroll.show', compact('payRun', 'statusLabels'));
    }

    /**
     * Show edit form for payroll (draft status only).
     */
    public function edit(PayRun $payRun): \Illuminate\Http\RedirectResponse|View
    {
        if ($payRun->status !== 1) {
            return back()->with('error', 'Only draft payroll runs can be edited.');
        }

        $statusLabels = [1 => 'Draft', 2 => 'Processing', 3 => 'Completed', 4 => 'Cancelled'];

        return view('payroll.edit', compact('payRun', 'statusLabels'));
    }

    /**
     * Update payroll details.
     */
    public function update(Request $request, PayRun $payRun)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'pay_date' => 'required|date',
            'status' => 'required|integer|in:1,2,3,4',
        ]);

        $payRun->update($validated);

        return redirect()->route('payroll.show', $payRun)->with('status', 'Payroll updated successfully.');
    }

    /**
     * Delete payroll run.
     */
    public function destroy(PayRun $payRun)
    {
        $payRun->update(['status' => 13]);

        return redirect()->route('payroll.index')->with('status', 'Payroll run deleted successfully.');
    }
}
