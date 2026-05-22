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
     * Build the query for index and export.
     */
    private function buildQuery(Request $request)
    {
        $query = PayRun::with('payslips')->where('status', '!=', 13);

        if ($request->filled('start_date')) {
            $query->where('period_start', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('period_end', '<=', $request->input('end_date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // If no date filters are active, default to current year
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereYear('period_start', now()->year);
        }

        return $query->orderBy('period_end', 'desc');
    }

    /**
     * Display the payroll dashboard.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'status'     => ['nullable', 'integer', 'in:1,2,3,4'],
        ]);

        $currentYear = now()->year;

        // Paginate the filtered pay runs
        $payRuns = $this->buildQuery($request)
            ->paginate(15)
            ->appends($request->query());

        $filters = $request->only(['start_date', 'end_date', 'status']);

        // Calculate YTD totals (Year-to-Date using all non-deleted pay runs for the current year)
        $ytdPayRuns = PayRun::whereYear('period_start', $currentYear)
            ->where('status', '!=', 13)
            ->get();

        $ytdGross = $ytdPayRuns->sum(function ($payRun) {
            return $payRun->payslips()->sum('gross_pay');
        });

        $ytdNet = $ytdPayRuns->sum(function ($payRun) {
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

        return view('payroll.index', compact('payRuns', 'ytdGross', 'ytdNet', 'statutoryAmount', 'filters'));
    }

    /**
     * Export pay runs to CSV.
     */
    public function export(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'status'     => ['nullable', 'integer', 'in:1,2,3,4'],
        ]);

        $payRuns = $this->buildQuery($request)->get();
        $filename = "pay_runs_export_" . now()->format('Ymd_His') . ".csv";

        $responseHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($payRuns) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'Pay Run ID',
                'Period Start',
                'Period End',
                'Pay Date',
                'Status',
                'Number of Employees',
                'Total Gross Pay',
                'Total Net Pay',
                'Total Deductions'
            ]);

            $statusLabels = [
                1 => 'Draft',
                2 => 'Processing',
                3 => 'Completed',
                4 => 'Cancelled'
            ];

            foreach ($payRuns as $payRun) {
                $employeeCount = $payRun->payslips->count();
                $grossPay = $payRun->payslips->sum('gross_pay');
                $netPay = $payRun->payslips->sum('net_pay');
                $deductions = $payRun->payslips->sum('total_deductions');

                fputcsv($file, [
                    $payRun->id,
                    $payRun->period_start ? $payRun->period_start->format('Y-m-d') : 'N/A',
                    $payRun->period_end ? $payRun->period_end->format('Y-m-d') : 'N/A',
                    $payRun->pay_date ? $payRun->pay_date->format('Y-m-d') : 'N/A',
                    $statusLabels[$payRun->status] ?? 'Unknown',
                    $employeeCount,
                    number_format($grossPay, 2, '.', ''),
                    number_format($netPay, 2, '.', ''),
                    number_format($deductions, 2, '.', '')
                ]);
            }

            fclose($file);
        }, 200, $responseHeaders);
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
            $loc = $p->location ?: 'General';
            $plottingMap[$p->employee_id][$loc][Carbon::parse($p->date)->format('Y-m-d')] = $p;
        }

        [$fieldRecordMap, $fieldSupervisorMap] = $this->fieldRecordMaps(array_keys($dates));

        // Find unique locations for each employee code based on field records in the dates
        $employeeLocations = [];
        $recordsForUniqueLocs = DB::table('field_records')
            ->select('empid', 'location')
            ->whereIn('Date', array_keys($dates))
            ->get();
        
        foreach ($recordsForUniqueLocs as $r) {
            $loc = $r->location ?: 'General';
            $employeeLocations[$r->empid][$loc] = true;
        }

        $employeeMap = $employees->keyBy('id');
        $employeeCodeMap = $employees->keyBy('employee_code');

        $gridData = [];
        foreach ($employees as $employee) {
            $locs = array_keys($employeeLocations[$employee->employee_code] ?? ['General' => true]);

            foreach ($locs as $locName) {
                $row = [
                    'employee' => $employee,
                    'location' => $locName,
                    'days' => []
                ];

                foreach (array_keys($dates) as $date) {
                    $plotting = $plottingMap[$employee->id][$locName][$date] ?? null;
                    $amount = $plotting ? $plotting->amount : 0.00;
                    $fieldRecord = $fieldRecordMap[$employee->employee_code][$locName][$date] ?? null;
                    $fieldSupervisor = $fieldRecord ? ($fieldSupervisorMap[$fieldRecord['sup_id']][$date] ?? null) : null;
                    $supervisorCode = $fieldRecord['sup_id'] ?? ($employee->manager?->employee_code ?? null);
                    
                    // Location resolution logic
                    $isSupervisor = $employee->user && $employee->user->role === 2;
                    $supervisor = $supervisorCode ? ($employeeCodeMap[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;

                    if ($isSupervisor) {
                        $location = $fieldRecord['location'] ?? ($fieldSupervisor['location'] ?? $locName);
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
                                : $locName;
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
        }

        return view('payroll.plotting-payment', compact('dates', 'gridData'));
    }
    /**
     * Display details for a specific date in plotting payment.
     */
    public function showPerDateDetails($date): View
    {
        $parsedDate = Carbon::parse($date);
        $dateFormatted = $parsedDate->format('M d, Y');

        $scannedEmployeeCodes = DB::table('field_records')
            ->where('Date', $date)
            ->distinct()
            ->pluck('empid')
            ->all();

        $employees = Employee::whereIn('employee_code', $scannedEmployeeCodes)
            ->whereNull('termination_date')
            ->with(['user', 'manager'])
            ->orderBy('first_name')
            ->get();

        [$fieldRecordMap, $fieldSupervisorMap] = $this->fieldRecordMaps([$date]);
        
        $employeeMap = $employees->keyBy('id');
        $employeeCodeMap = $employees->keyBy('employee_code');
        
        $employeeLocations = [];
        $recordsForUniqueLocs = DB::table('field_records')
            ->select('empid', 'location')
            ->where('Date', $date)
            ->get();
        
        foreach ($recordsForUniqueLocs as $r) {
            $loc = $r->location ?: 'General';
            $employeeLocations[$r->empid][$loc] = true;
        }

        // Fetch existing plottings for this date to pre-fill amounts
        $plottings = EmployeePlotting::where('date', $date)->get();
        $plottingMap = [];
        foreach ($plottings as $p) {
            $loc = $p->location ?: 'General';
            $plottingMap[$p->employee_id][$loc] = $p;
        }

        $employeeData = [];
        foreach ($employees as $employee) {
            $locs = array_keys($employeeLocations[$employee->employee_code] ?? ['General' => true]);
            
            foreach ($locs as $locName) {
                $fieldRecord = $fieldRecordMap[$employee->employee_code][$locName][$date] ?? null;
                $fieldSupervisor = $fieldRecord ? ($fieldSupervisorMap[$fieldRecord['sup_id']][$date] ?? null) : null;
                $supervisorCode = $fieldRecord['sup_id'] ?? ($employee->manager?->employee_code ?? null);
                
                $isSupervisor = $employee->user && $employee->user->role === 2;
                $supervisor = $supervisorCode ? ($employeeCodeMap[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;

                if ($isSupervisor) {
                    $location = $fieldRecord['location'] ?? ($fieldSupervisor['location'] ?? $locName);
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
                            : $locName;
                    }
                    $svName = $supervisor ? ($supervisor->first_name . ' ' . $supervisor->last_name) : 'None';
                }

                $plotting = $plottingMap[$employee->id][$location] ?? null;

                $employeeData[] = [
                    'id' => $employee->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'workplace' => $location,
                    'supervisor' => $svName,
                    'amount' => $plotting ? $plotting->amount : null,
                ];
            }
        }

        return view('payroll.per-date', compact('dateFormatted', 'employeeData', 'date'));
    }

    /**
     * Save the entire payment plotting grid.
     */
    public function savePlottingPayment(Request $request)
    {
        $entries = $request->input('entries', []);

        foreach ($entries as $employeeId => $locations) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                continue;
            }

            foreach ($locations as $locationName => $dates) {
                foreach ($dates as $date => $amount) {
                    $cleanAmount = (float) str_replace([',', '$', ' '], '', $amount);

                    $plotting = EmployeePlotting::where('employee_id', $employee->id)
                        ->where('date', $date)
                        ->where('location', $locationName)
                        ->first();

                    if ($plotting) {
                        $plotting->update([
                            'amount' => $cleanAmount
                        ]);
                    } else {
                        $isSupervisor = $employee->user && $employee->user->role === 2;
                        $supervisorId = null;

                        // Resolve supervisor for this location and date
                        $fieldRecord = DB::table('field_records')
                            ->where('empid', $employee->employee_code)
                            ->where('Date', $date)
                            ->where('location', $locationName)
                            ->first();

                        if ($fieldRecord) {
                            $supervisorEmployee = Employee::where('employee_code', $fieldRecord->sup_id)->first();
                            if ($supervisorEmployee) {
                                $supervisorId = $supervisorEmployee->id;
                            }
                        } else {
                            if ($employee->manager_id) {
                                $manager = $employee->manager;
                                $isManagerSupervisor = $manager && $manager->user && $manager->user->role === 2;
                                if ($isManagerSupervisor) {
                                    $supervisorId = $employee->manager_id;
                                }
                            }
                        }

                        EmployeePlotting::create([
                            'employee_id' => $employee->id,
                            'date' => $date,
                            'location' => $locationName,
                            'supervisor_id' => $supervisorId,
                            'amount' => $cleanAmount
                        ]);
                    }
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
            ->get();

        $plottingsMap = [];
        foreach ($plottings as $p) {
            $loc = $p->location ?: 'General';
            $plottingsMap[Carbon::parse($p->date)->format('Y-m-d')][$loc] = $p;
        }

        // Get all field records for this employee for the visible dates
        $employeeRecords = DB::table('field_records')
            ->where('empid', $employee->employee_code)
            ->whereIn('Date', array_keys($dates))
            ->orderBy('time')
            ->orderBy('id')
            ->get()
            ->groupBy('Date');

        [$fieldRecordMap, $fieldSupervisorMap] = $this->fieldRecordMaps(array_keys($dates));
        $employeeCodeMap = Employee::whereNull('termination_date')->get()->keyBy('employee_code');

        $weekData = [];
        foreach ($dates as $dateString => $dateLabel) {
            $recordsForDate = $employeeRecords->get($dateString);

            if ($recordsForDate && $recordsForDate->isNotEmpty()) {
                // Unique locations worked on this date
                $uniqueLocs = $recordsForDate->pluck('location')->unique()->all();
                
                foreach ($uniqueLocs as $loc) {
                    $locName = $loc ?: 'General';
                    
                    // Find a record for this specific location to get the supervisor & notes
                    $fieldRecord = $recordsForDate->first(fn($r) => ($r->location ?: 'General') === $locName);
                    
                    $fieldSupervisor = $fieldRecord ? ($fieldSupervisorMap[$fieldRecord->sup_id][$dateString] ?? null) : null;
                    $supervisorCode = $fieldRecord ? $fieldRecord->sup_id : ($employee->manager?->employee_code ?? null);
                    $supervisor = $supervisorCode ? ($employeeCodeMap[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;
                    
                    $location = $locName;
                    $supervisorName = 'None';
                    
                    $isSupervisor = $employee->user && $employee->user->role === 2;
                    if ($isSupervisor) {
                        $supervisorName = 'None';
                    } else {
                        if ($supervisor) {
                            $supervisorName = $supervisor->first_name . ' ' . $supervisor->last_name;
                        }
                    }

                    $plotting = $plottingsMap[$dateString][$locName] ?? null;
                    $amount = $plotting ? $plotting->amount : 0.00;

                    $weekData[] = [
                        'date_string' => $dateString,
                        'date' => $dateLabel,
                        'workplace' => $location,
                        'supervisor' => $supervisorName,
                        'supervisor_code' => $supervisorCode,
                        'supervisor_note' => $fieldRecord->notes ?? ($fieldSupervisor['notes'] ?? null),
                        'amount' => $amount
                    ];
                }
            } else {
                // Fallback logic when there are no field records for this employee on this date
                $location = 'General';
                $supervisorName = 'None';
                $supervisorCode = $employee->manager?->employee_code ?? null;
                $supervisor = $supervisorCode ? ($employeeCodeMap[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;

                $isSupervisor = $employee->user && $employee->user->role === 2;

                if ($isSupervisor) {
                    // It's a supervisor, look at their own supervisorMap if any
                    $fieldSupervisor = $fieldSupervisorMap[$employee->employee_code][$dateString] ?? null;
                    $location = $fieldSupervisor['location'] ?? 'General';
                } else {
                    $dailySupervisorId = $employee->manager_id;
                    if ($dailySupervisorId) {
                        $fallbackSupervisor = $employee->manager?->employee_code
                            ? ($employeeCodeMap[$employee->manager->employee_code] ?? Employee::find($dailySupervisorId))
                            : Employee::find($dailySupervisorId);
                        if ($fallbackSupervisor) {
                            $supervisorName = $fallbackSupervisor->first_name . ' ' . $fallbackSupervisor->last_name;
                            $location = $fieldSupervisorMap[$fallbackSupervisor->employee_code][$dateString]['location'] ?? 'General';
                        }
                    }
                    if ($supervisor) {
                        $supervisorName = $supervisor->first_name . ' ' . $supervisor->last_name;
                    }
                }

                $plotting = $plottingsMap[$dateString][$location] ?? null;
                $amount = $plotting ? $plotting->amount : 0.00;

                $weekData[] = [
                    'date_string' => $dateString,
                    'date' => $dateLabel,
                    'workplace' => $location,
                    'supervisor' => $supervisorName,
                    'supervisor_code' => $supervisorCode,
                    'supervisor_note' => null,
                    'amount' => $amount
                ];
            }
        }

        return view('payroll.per-employee', compact('employee', 'weekData'));
    }

    /**
     * Save plotting details for a single employee.
     */
    public function savePlottingEmployee(Request $request, Employee $employee)
    {
        $entries = $request->input('entries', []);

        foreach ($entries as $date => $locations) {
            foreach ($locations as $locationName => $amount) {
                $cleanAmount = (float) str_replace([',', '$', ' '], '', $amount);

                $plotting = EmployeePlotting::where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->where('location', $locationName)
                    ->first();

                if ($plotting) {
                    $plotting->update([
                        'amount' => $cleanAmount
                    ]);
                } else {
                    $isSupervisor = $employee->user && $employee->user->role === 2;
                    $supervisorId = null;

                    // Resolve supervisor for this location and date
                    $fieldRecord = DB::table('field_records')
                        ->where('empid', $employee->employee_code)
                        ->where('Date', $date)
                        ->where('location', $locationName)
                        ->first();

                    if ($fieldRecord) {
                        $supervisorEmployee = Employee::where('employee_code', $fieldRecord->sup_id)->first();
                        if ($supervisorEmployee) {
                            $supervisorId = $supervisorEmployee->id;
                        }
                    } else {
                        if ($employee->manager_id) {
                            $manager = $employee->manager;
                            $isManagerSupervisor = $manager && $manager->user && $manager->user->role === 2;
                            if ($isManagerSupervisor) {
                                $supervisorId = $employee->manager_id;
                            }
                        }
                    }

                    EmployeePlotting::create([
                        'employee_id' => $employee->id,
                        'date' => $date,
                        'location' => $locationName,
                        'supervisor_id' => $supervisorId,
                        'amount' => $cleanAmount
                    ]);
                }
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

        // Fetch all field records for this date
        $allRecords = DB::table('field_records')
            ->where('Date', $date)
            ->get();

        // Group field records by employee code
        $employeeRecords = $allRecords->groupBy('empid');

        // Group field records by supervisor code for supervisor fallback
        $supervisorRecords = $allRecords->groupBy('sup_id');

        foreach ($allEmployees as $emp) {
            $isSupervisor = $emp->user && $emp->user->role === 2;
            
            // Get this employee's records for this day
            $records = $employeeRecords->get($emp->employee_code);

            if ($records && $records->isNotEmpty()) {
                // The employee has scanned records on this day.
                // For each unique location:
                $uniqueLocs = $records->pluck('location')->unique();
                foreach ($uniqueLocs as $loc) {
                    $locName = $loc ?: 'General';
                    if ($locName === $workplaceName) {
                        // Find the first record for this location to get supervisor code
                        $record = $records->first(fn($r) => ($r->location ?: 'General') === $locName);
                        
                        $plotting = EmployeePlotting::where('employee_id', $emp->id)
                            ->where('date', $date)
                            ->where('location', $workplaceName)
                            ->first();

                        $supervisorCode = $record->sup_id;
                        $supervisor = $employeeByCode->get($supervisorCode);
                        $supervisorName = 'None';
                        if ($supervisor) {
                            $supervisorName = $supervisor->first_name . ' ' . $supervisor->last_name;
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
            } else {
                // Fallback logic when employee has no scanned records
                $resolvedLoc = 'General';
                $supervisorCode = $emp->manager?->employee_code ?? null;
                $supervisor = $supervisorCode ? $employeeByCode->get($supervisorCode) : null;

                if ($isSupervisor) {
                    // Check supervisor's own records
                    $supRecs = $supervisorRecords->get($emp->employee_code);
                    if ($supRecs && $supRecs->isNotEmpty()) {
                        $resolvedLoc = $supRecs->first()->location ?: 'General';
                    }
                } else {
                    // Regular employee fallback
                    $dailySupervisorId = $emp->manager_id;
                    if ($dailySupervisorId) {
                        $sv = $allEmployees->firstWhere('id', $dailySupervisorId);
                        if ($sv) {
                            $svRecs = $supervisorRecords->get($sv->employee_code);
                            if ($svRecs && $svRecs->isNotEmpty()) {
                                $resolvedLoc = $svRecs->first()->location ?: 'General';
                            }
                        }
                    }
                }

                if ($resolvedLoc === $workplaceName) {
                    $plotting = EmployeePlotting::where('employee_id', $emp->id)
                        ->where('date', $date)
                        ->where('location', $workplaceName)
                        ->first();

                    $supervisorName = 'None';
                    if ($supervisor) {
                        $supervisorName = $supervisor->first_name . ' ' . $supervisor->last_name;
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
                'location' => $record->location ?: 'General',
                'notes' => $record->notes,
            ];

            $loc = $record->location ?: 'General';
            $employeeMap[$record->empid][$loc][$record->Date] = $payload;
            $supervisorMap[$record->sup_id][$record->Date] = $payload;
        }

        return [$employeeMap, $supervisorMap];
    }

    /**
     * Show form to create a new pay run.
     */
    public function create(): View
    {
        $employees = \App\Models\Employee::whereNull('termination_date')->orderBy('last_name')->get();

        $activePayRuns = PayRun::whereNotIn('status', [4, 13])
            ->with('payslips')
            ->get()
            ->map(function ($payRun) {
                return [
                    'id' => $payRun->id,
                    'name' => $payRun->name,
                    'period_start' => Carbon::parse($payRun->period_start)->toDateString(),
                    'period_end' => Carbon::parse($payRun->period_end)->toDateString(),
                    'status_label' => $payRun->status === 3 ? 'Completed' : ($payRun->status === 2 ? 'Processing' : 'Draft'),
                    'employee_ids' => $payRun->payslips->pluck('employee_id')->all(),
                ];
            });

        return view('payroll.create', compact('employees', 'activePayRuns'));
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

        // Check for duplicate pay runs (exact same period, active and NOT completed status)
        $duplicatePayRun = PayRun::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->whereNotIn('status', [3, 4, 13])
            ->first();

        if ($duplicatePayRun) {
            return back()
                ->withInput()
                ->withErrors(['period_start' => 'A pay run for this exact period (' . $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d') . ') already exists and is not yet completed.']);
        }

        // Check if selected employees are already included in any overlapping active/completed pay runs
        $overlappingPayRuns = PayRun::whereNotIn('status', [4, 13])
            ->where('period_start', '<=', $periodEnd->toDateString())
            ->where('period_end', '>=', $periodStart->toDateString())
            ->get();

        if ($overlappingPayRuns->isNotEmpty()) {
            $overlappingPayRunIds = $overlappingPayRuns->pluck('id');
            $alreadyPaidOrProcessing = Employee::whereIn('id', $validated['employee_ids'])
                ->whereHas('payslips', function ($query) use ($overlappingPayRunIds) {
                    $query->whereIn('pay_run_id', $overlappingPayRunIds);
                })
                ->get();

            if ($alreadyPaidOrProcessing->isNotEmpty()) {
                $names = $alreadyPaidOrProcessing->map(fn($emp) => $emp->full_name)->implode(', ');
                return back()
                    ->withInput()
                    ->withErrors(['employee_ids' => 'The following selected employees are already included in another pay run (completed or processing) for an overlapping period: ' . $names]);
            }
        }

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

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);

        // Check for duplicate pay runs (exact same period, excluding this one, active and NOT completed status)
        $duplicatePayRun = PayRun::where('id', '!=', $payRun->id)
            ->where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->whereNotIn('status', [3, 4, 13])
            ->first();

        if ($duplicatePayRun) {
            return back()
                ->withInput()
                ->withErrors(['period_start' => 'A pay run for this exact period (' . $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d') . ') already exists and is not yet completed.']);
        }

        // Check if employees in this pay run are already included in any other overlapping active/completed pay runs
        $overlappingPayRuns = PayRun::where('id', '!=', $payRun->id)
            ->whereNotIn('status', [4, 13])
            ->where('period_start', '<=', $periodEnd->toDateString())
            ->where('period_end', '>=', $periodStart->toDateString())
            ->get();

        if ($overlappingPayRuns->isNotEmpty()) {
            $overlappingPayRunIds = $overlappingPayRuns->pluck('id');
            $employeeIds = $payRun->payslips()->pluck('employee_id')->toArray();

            if (!empty($employeeIds)) {
                $alreadyPaidOrProcessing = Employee::whereIn('id', $employeeIds)
                    ->whereHas('payslips', function ($query) use ($overlappingPayRunIds) {
                        $query->whereIn('pay_run_id', $overlappingPayRunIds);
                    })
                    ->get();

                if ($alreadyPaidOrProcessing->isNotEmpty()) {
                    $names = $alreadyPaidOrProcessing->map(fn($emp) => $emp->full_name)->implode(', ');
                    return back()
                        ->withInput()
                        ->withErrors(['period_start' => 'The following employees in this pay run are already included in another pay run (completed or processing) for the overlapping period: ' . $names]);
                }
            }
        }

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
