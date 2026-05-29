<?php

namespace App\Http\Controllers;

use App\Models\PayRun;
use App\Models\Employee;
use App\Models\EmployeePlotting;
use App\Models\PreviousClaim;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $payRuns = $this->buildQuery($request)
            ->get();

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
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->role !== 4) {
            abort(403, 'Unauthorized action. Exports are restricted to HR only.');
        }

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
    public function plottingPayment(Request $request): View
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $dates = $this->fieldRecordDates($fromDate, $toDate);
        $dateKeys = array_keys($dates);

        $resolvedFromDate = !empty($dateKeys) ? array_key_first($dates) : null;
        $resolvedToDate = !empty($dateKeys) ? array_key_last($dates) : null;

        $scannedEmployeeCodes = DB::table('field_records')
            ->whereIn('Date', $dateKeys)
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

        $plottings = EmployeePlotting::whereIn('date', $dateKeys)->get();
        $plottingMap = [];
        foreach ($plottings as $p) {
            $loc = $p->location ?: 'General';
            $plottingMap[$p->empid][$loc][Carbon::parse($p->date)->format('Y-m-d')] = $p;
        }

        // Get ALL field records grouped by empid+date so we can show multiple entries per cell
        $allFieldRecords = DB::table('field_records')
            ->select('empid', 'sup_id', 'Date', 'location', 'notes', 'payroll_note', 'time', 'id', 'session_id', 'work_status')
            ->whereIn('Date', $dateKeys)
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        // Build a map: empid => date => [ array of records ]
        $fieldRecordsByEmpDate = [];
        foreach ($allFieldRecords as $record) {
            $fieldRecordsByEmpDate[$record->empid][$record->Date][] = $record;
        }

        // Build supervisor map for fallback location resolution
        $supervisorMap = [];
        foreach ($allFieldRecords as $record) {
            $supervisorMap[$record->sup_id][$record->Date] = [
                'sup_id' => $record->sup_id,
                'location' => $record->location ?: 'General',
                'notes' => $record->notes,
            ];
        }

        $employeeMap = $employees->keyBy('id');
        $employeeCodeMap = $employees->keyBy('employee_code');

        $gridData = [];
        foreach ($employees as $employee) {
            $isSupervisor = $employee->user && $employee->user->role === 2;

            $row = [
                'employee' => $employee,
                'days' => []
            ];

            foreach ($dateKeys as $date) {
                $records = $fieldRecordsByEmpDate[$employee->employee_code][$date] ?? [];
                $entries = [];

                if (!empty($records)) {
                    $seenSessions = [];
                    foreach ($records as $record) {
                        $locName = $record->location ?: 'General';

                        // Deduplication logic
                        $dedupKey = $record->session_id
                            ? 'session_' . $record->session_id
                            : 'loc_' . $locName;

                        if (isset($seenSessions[$dedupKey])) {
                            continue; // Skip duplicates for the same session or same location (if no session)
                        }
                        $seenSessions[$dedupKey] = true;

                        $supervisorCode = $record->sup_id;
                        $fieldSupervisor = $supervisorMap[$supervisorCode][$date] ?? null;
                        $supervisor = $supervisorCode ? ($employeeCodeMap[$supervisorCode] ?? Employee::where('employee_code', $supervisorCode)->first()) : null;

                        if ($isSupervisor) {
                            $location = $record->location ?: ($fieldSupervisor['location'] ?? $locName);
                            $svName = 'None';
                        } else {
                            if (!empty($record->location)) {
                                $location = $record->location;
                            } elseif ($fieldSupervisor && !empty($fieldSupervisor['location'])) {
                                $location = $fieldSupervisor['location'];
                            } else {
                                $dailySupervisorId = $employee->manager_id;
                                $dailySupervisor = $dailySupervisorId ? ($employeeMap[$dailySupervisorId] ?? Employee::find($dailySupervisorId)) : null;
                                $location = ($dailySupervisor && isset($supervisorMap[$dailySupervisor->employee_code][$date]))
                                    ? $supervisorMap[$dailySupervisor->employee_code][$date]['location']
                                    : $locName;
                            }
                            $svName = $supervisor ? ($supervisor->first_name . ' ' . $supervisor->last_name) : 'None';
                        }

                        $plotting = $plottingMap[$employee->employee_code][$locName][$date] ?? null;
                        $amount = $plotting ? $plotting->amount : 0.00;

                        $entries[] = [
                            'record_id' => $record->id,
                            'amount' => $amount,
                            'location' => $location,
                            'supervisor_name' => $svName,
                            'supervisor_code' => $supervisorCode,
                            'supervisor_note' => $record->notes,
                            'payroll_note' => $record->payroll_note,
                            'posted' => $plotting ? $plotting->posted : false,
                        ];
                    }
                } else {
                    // No field records for this date
                    // Only show existing plottings if they manually exist in the DB without a field record.
                    if (isset($plottingMap[$employee->employee_code])) {
                        foreach ($plottingMap[$employee->employee_code] as $loc => $datesObj) {
                            if (isset($datesObj[$date])) {
                                $plotting = $datesObj[$date];
                                $entries[] = [
                                    'record_id' => null,
                                    'amount' => $plotting->amount,
                                    'location' => $loc,
                                    'supervisor_name' => 'None',
                                    'supervisor_code' => $plotting->sup_id,
                                    'supervisor_note' => null,
                                    'payroll_note' => null,
                                    'posted' => $plotting->posted,
                                ];
                            }
                        }
                    }
                }

                $row['days'][$date] = $entries;
            }

            $gridData[] = $row;
        }

        return view('payroll.plotting-payment', compact('dates', 'gridData', 'resolvedFromDate', 'resolvedToDate'));
    }

    /**
     * Find dates with field records that don't have paid/posted plottings.
     */
    public function findMissedPlottings(Request $request)
    {
        $targetDate = $request->input('target_date', date('Y-m-d'));

        // Query distinct dates where an employee has a field record but no paid/posted plotting
        $missedDates = DB::select("
            SELECT DISTINCT fr.Date
            FROM field_records fr
            LEFT JOIN employee_plottings ep
                ON fr.empid COLLATE utf8mb4_unicode_ci = ep.empid COLLATE utf8mb4_unicode_ci
                AND fr.Date = ep.date
                AND COALESCE(ep.location, 'General') COLLATE utf8mb4_unicode_ci =
                    COALESCE(NULLIF(fr.location, ''), 'General') COLLATE utf8mb4_unicode_ci
            WHERE fr.Date <= ?
              AND (ep.id IS NULL OR ep.posted = 0 OR ep.payment_status != 'paid')
            ORDER BY fr.Date DESC
        ", [$targetDate]);

        $dates = array_map(function ($row) {
            return $row->Date;
        }, $missedDates);

        return response()->json(['dates' => $dates]);
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
            $plottingMap[$p->empid][$loc] = $p;
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

                $plotting = $plottingMap[$employee->employee_code][$location] ?? null;

                $employeeData[] = [
                    'id' => $employee->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'workplace' => $location,
                    'supervisor' => $svName,
                    'amount' => $plotting ? $plotting->amount : null,
                    'posted' => $plotting ? $plotting->posted : false,
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
        $payrollNotes = $request->input('payroll_notes', []);

        foreach ($entries as $employeeId => $locations) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                continue;
            }

            foreach ($locations as $locationName => $dates) {
                foreach ($dates as $date => $amount) {
                    $cleanAmount = (float) str_replace([',', '$', ' '], '', $amount);

                    // Normalize location key to avoid mismatches between empty/whitespace and 'General'
                    $locationKey = trim((string) $locationName);
                    if ($locationKey === '') {
                        $locationKey = 'General';
                    }

                    $plotting = EmployeePlotting::where('empid', $employee->employee_code)
                        ->where('date', $date)
                        ->where('location', $locationKey)
                        ->first();

                    if ($plotting) {
                        if ($plotting->posted) {
                            continue; // Skip updating if already posted
                        }
                        $plotting->update([
                            'amount' => $cleanAmount,
                            'payment_status' => 'paid',
                            'posted' => true
                        ]);
                        $this->checkAndCreatePreviousClaimForPlotting($employee, $date, $locationKey, $cleanAmount);
                    } else {
                        // Resolve supervisor code
                        $supCode = null;
                        $fieldRecord = DB::table('field_records')
                            ->where('empid', $employee->employee_code)
                            ->where('Date', $date)
                            ->where('location', $locationName)
                            ->first();

                        if ($fieldRecord) {
                            $supCode = $fieldRecord->sup_id;
                        } elseif ($employee->manager_id) {
                            $manager = $employee->manager;
                            if ($manager && $manager->user && $manager->user->role === 2) {
                                $supCode = $manager->employee_code;
                            }
                        }

                        try {
                            EmployeePlotting::create([
                                'empid' => $employee->employee_code,
                                'date' => $date,
                                'location' => $locationKey,
                                'sup_id' => $supCode,
                                'amount' => $cleanAmount,
                                'payment_status' => 'paid',
                                'posted' => true
                            ]);
                        } catch (\Illuminate\Database\QueryException $e) {
                            // Handle race or duplicate insertion by reconciling with existing record
                            $existing = EmployeePlotting::where('empid', $employee->employee_code)
                                ->where('date', $date)
                                ->where('location', $locationKey)
                                ->first();
                            if ($existing) {
                                if ($existing->posted) {
                                    continue;
                                }
                                $existing->update([
                                    'amount' => $cleanAmount,
                                    'payment_status' => 'paid',
                                    'posted' => true
                                ]);
                            } else {
                                throw $e; // rethrow unexpected DB errors
                            }
                        }

                        $this->checkAndCreatePreviousClaimForPlotting($employee, $date, $locationKey, $cleanAmount);
                    }
                }
            }
        }

        return redirect()->route('payroll.plotting-payment', $request->only(['from_date', 'to_date']))
            ->with('success', 'Plotting payments submitted successfully.');
    }

    public function savePayrollNote(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'location' => 'required|string',
            'note' => 'nullable|string',
        ]);

        $employee = Employee::find($validated['employee_id']);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        DB::table('field_records')
            ->where('empid', $employee->employee_code)
            ->where('Date', $validated['date'])
            ->where('location', $validated['location'])
            ->update(['payroll_note' => $validated['note']]);

        return response()->json(['success' => true]);
    }

    /**
     * Show plotting details for a single employee.
     */
    public function showPlottingEmployee(Request $request, Employee $employee): View
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $dates = $this->fieldRecordDates($fromDate, $toDate);
        $dateKeys = array_keys($dates);

        $resolvedFromDate = !empty($dateKeys) ? array_key_first($dates) : null;
        $resolvedToDate = !empty($dateKeys) ? array_key_last($dates) : null;

        $plottings = EmployeePlotting::where('empid', $employee->employee_code)
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
                        'supervisor_note' => $fieldRecord->notes,
                        'amount' => $amount,
                        'posted' => $plotting ? $plotting->posted : false,
                    ];
                }
            } else {
                // Fallback logic when there are no field records for this employee on this date
                // Only show existing plottings
                if (isset($plottingsMap[$dateString])) {
                    foreach ($plottingsMap[$dateString] as $loc => $plotting) {
                        $weekData[] = [
                            'date_string' => $dateString,
                            'date' => $dateLabel,
                            'workplace' => $loc,
                            'supervisor' => 'None',
                            'supervisor_code' => $plotting->sup_id,
                            'supervisor_note' => null,
                            'amount' => $plotting->amount,
                            'posted' => $plotting->posted,
                        ];
                    }
                }
            }
        }

        return view('payroll.per-employee', compact('employee', 'weekData', 'resolvedFromDate', 'resolvedToDate'));
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

                // Normalize location and guard against empty/whitespace
                $locationKey = trim((string) $locationName);
                if ($locationKey === '') {
                    $locationKey = 'General';
                }

                $plotting = EmployeePlotting::where('empid', $employee->employee_code)
                    ->where('date', $date)
                    ->where('location', $locationKey)
                    ->first();

                if ($plotting) {
                    if ($plotting->posted) {
                        continue;
                    }
                    $plotting->update([
                        'amount' => $cleanAmount,
                        'payment_status' => 'paid',
                        'posted' => true
                    ]);
                    $this->checkAndCreatePreviousClaimForPlotting($employee, $date, $locationKey, $cleanAmount);
                } else {
                    $supCode = null;
                    $fieldRecord = DB::table('field_records')
                        ->where('empid', $employee->employee_code)
                        ->where('Date', $date)
                        ->where('location', $locationName)
                        ->first();

                    if ($fieldRecord) {
                        $supCode = $fieldRecord->sup_id;
                    } elseif ($employee->manager_id) {
                        $manager = $employee->manager;
                        if ($manager && $manager->user && $manager->user->role === 2) {
                            $supCode = $manager->employee_code;
                        }
                    }

                    try {
                        EmployeePlotting::create([
                            'empid' => $employee->employee_code,
                            'date' => $date,
                            'location' => $locationKey,
                            'sup_id' => $supCode,
                            'amount' => $cleanAmount,
                            'payment_status' => 'paid',
                            'posted' => true
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        $existing = EmployeePlotting::where('empid', $employee->employee_code)
                            ->where('date', $date)
                            ->where('location', $locationKey)
                            ->first();
                        if ($existing) {
                            if ($existing->posted) {
                                continue;
                            }
                            $existing->update([
                                'amount' => $cleanAmount,
                                'payment_status' => 'paid',
                                'posted' => true
                            ]);
                        } else {
                            throw $e;
                        }
                    }

                    $this->checkAndCreatePreviousClaimForPlotting($employee, $date, $locationKey, $cleanAmount);
                }
            }
        }

        return redirect()->route('payroll.plotting-payment', $request->only(['from_date', 'to_date']))
            ->with('success', "Plotting payments for {$employee->first_name} submitted successfully.");
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

                        $plotting = EmployeePlotting::where('empid', $emp->employee_code)
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
                            'amount' => $plotting ? $plotting->amount : 0.00,
                            'posted' => $plotting ? $plotting->posted : false
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
                    $plotting = EmployeePlotting::where('empid', $emp->employee_code)
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
                        'amount' => $plotting ? $plotting->amount : 0.00,
                        'posted' => $plotting ? $plotting->posted : false
                    ];
                }
            }
        }

        return view('payroll.work-location-details', compact('date', 'workplaceName', 'employeeData'));
    }

    /**
     * Build a lookup of work locations from field records keyed by employee code and date.
     */
    private function fieldRecordDates(?string $fromDate = null, ?string $toDate = null): array
    {
        if (empty($fromDate) || empty($toDate)) {
            $latestDate = DB::table('field_records')->max('Date');
            if ($latestDate) {
                $latestCarbon = Carbon::parse($latestDate);
                $fromDate = $latestCarbon->copy()->startOfWeek()->toDateString();
                $toDate = $latestCarbon->copy()->endOfWeek()->toDateString();
            } else {
                $fromDate = Carbon::now()->startOfWeek()->toDateString();
                $toDate = Carbon::now()->endOfWeek()->toDateString();
            }
        }

        $start = Carbon::parse($fromDate);
        $end = Carbon::parse($toDate);

        // Limit the range to prevent excessive memory/column usage (max 31 days)
        if ($start->diffInDays($end) > 31) {
            $end = $start->copy()->addDays(31);
        }

        $dates = [];
        $current = $start->copy();
        while ($current->lessThanOrEqualTo($end)) {
            $dateStr = $current->toDateString();
            $dates[$dateStr] = $current->format('M d');
            $current->addDay();
        }

        return $dates;
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
            ->with('payslips.governmentContributions')
            ->get()
            ->map(function ($payRun) {
                $governmentContributionCount = $payRun->payslips
                    ->flatMap(fn ($payslip) => $payslip->governmentContributions)
                    ->count();

                return [
                    'id' => $payRun->id,
                    'name' => $payRun->name,
                    'period_start' => Carbon::parse($payRun->period_start)->toDateString(),
                    'period_end' => Carbon::parse($payRun->period_end)->toDateString(),
                    'status_label' => $payRun->status === 3 ? 'Completed' : ($payRun->status === 2 ? 'Processing' : 'Draft'),
                    'employee_ids' => $payRun->payslips->pluck('employee_id')->all(),
                    'deduct_government_contributions' => (bool) ($payRun->deduct_government_contributions ?? false),
                    'government_contribution_count' => $governmentContributionCount,
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
            'deduct_government_contributions' => ['nullable', 'boolean'],
        ]);

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);
        $requestedDeductGov = $request->boolean('deduct_government_contributions');
        $governmentContributionLocked = $this->monthAlreadyHasGovernmentContributions($periodEnd);
        $deductGovernmentContributions = $requestedDeductGov && ! $governmentContributionLocked;

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
            'deduct_government_contributions' => $deductGovernmentContributions,
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

    private function monthAlreadyHasGovernmentContributions(Carbon $periodEnd): bool
    {
        $query = PayRun::query()
            ->whereYear('period_end', $periodEnd->year)
            ->whereMonth('period_end', $periodEnd->month)
            ->whereNotIn('status', [4, 13]);

        if (Schema::hasColumn('pay_runs', 'deduct_government_contributions')) {
            $query->where(function ($subQuery) {
                $subQuery->where('deduct_government_contributions', true)
                    ->orWhereHas('payslips.governmentContributions');
            });
        } else {
            $query->whereHas('payslips.governmentContributions');
        }

        return $query->exists();
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
        if ($payRun->status == 3) {
            return redirect()->route('payroll.index')->with('error', 'Completed payroll runs cannot be deleted.');
        }

        // Soft delete the payroll run:
        $payRun->update(['status' => 13]);

        // Delete associated payslips (which cascades to payslip line items)
        $payRun->payslips()->delete();

        // Reset linked claims and disputes so they are credited to the next pay run
        \App\Models\PreviousClaim::where('pay_run_id', $payRun->id)->update(['pay_run_id' => null]);

        \App\Models\PayslipDispute::where('adjustment_pay_run_id', $payRun->id)->update([
            'adjustment_pay_run_id' => null,
            'adjustment_payslip_id' => null,
        ]);

        return redirect()->route('payroll.index')->with('status', 'Payroll run deleted successfully.');
    }

    /**
     * Check if a completed payrun covers the given date, and if so,
     * create or update a PreviousClaim for the employee.
     */
    private function checkAndCreatePreviousClaimForPlotting(Employee $employee, string $date, string $locationName, float $amount): void
    {
        $completedPayRun = PayRun::where('status', 3) // 3 = Completed
            ->where('period_start', '<=', $date)
            ->where('period_end', '>=', $date)
            ->first();

        if ($completedPayRun) {
            $description = "Auto-generated: Plotted Payment for {$date} at {$locationName}";

            $existingClaim = PreviousClaim::where('employee_id', $employee->id)
                ->where('claim_date', $date)
                ->where('status', PreviousClaim::STATUS_PENDING)
                ->where('description', 'like', 'Auto-generated: Plotted Payment for%')
                ->first();

            if ($amount <= 0) {
                if ($existingClaim) {
                    $existingClaim->delete();
                }
            } else {
                if ($existingClaim) {
                    $existingClaim->update([
                        'amount' => $amount,
                        'description' => $description,
                        'claim_type' => 'Late Plotted Payment',
                    ]);
                } else {
                    PreviousClaim::create([
                        'employee_id' => $employee->id,
                        'claim_type' => 'Late Plotted Payment',
                        'claim_date' => $date,
                        'amount' => $amount,
                        'description' => $description,
                        'status' => PreviousClaim::STATUS_PENDING,
                        'submitted_by' => auth()->id(),
                    ]);
                }
            }
        }
    }
}
