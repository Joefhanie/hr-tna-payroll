<?php

namespace App\Http\Controllers;

use App\Models\PayRun;
use App\Models\Employee;
use App\Models\SupervisorAssignment;
use App\Models\EmployeePlotting;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $dates = [
            '2026-05-18' => 'May 18',
            '2026-05-19' => 'May 19',
            '2026-05-20' => 'May 20',
            '2026-05-21' => 'May 21',
            '2026-05-22' => 'May 22',
        ];

        // Fetch both regular employees and supervisors (exclude role = 4 / HR)
        $employees = Employee::whereNull('termination_date')
            ->where(function($query) {
                $query->whereHas('user', function($q) {
                    $q->where('role', '!=', 4);
                })->orWhereDoesntHave('user');
            })
            ->orderBy('first_name')
            ->get();

        $plottings = EmployeePlotting::whereIn('date', array_keys($dates))->get();
        $plottingMap = [];
        foreach ($plottings as $p) {
            $plottingMap[$p->employee_id][Carbon::parse($p->date)->format('Y-m-d')] = $p;
        }

        $assignments = SupervisorAssignment::whereIn('date', array_keys($dates))->get();
        $assignmentMap = [];
        foreach ($assignments as $a) {
            $assignmentMap[$a->supervisor_id][Carbon::parse($a->date)->format('Y-m-d')] = $a->location;
        }

        $gridData = [];
        foreach ($employees as $employee) {
            $row = [
                'employee' => $employee,
                'days' => []
            ];

            foreach (array_keys($dates) as $date) {
                $plotting = $plottingMap[$employee->id][$date] ?? null;
                $amount = $plotting ? $plotting->amount : 0.00;
                
                // Location resolution logic
                $isSupervisor = $employee->user && $employee->user->role === 2;

                if ($isSupervisor) {
                    // Supervisors ALWAYS resolve directly from their daily assignment!
                    $location = $assignmentMap[$employee->id][$date] ?? 'General';
                } else {
                    // Regular employees:
                    if ($plotting && $plotting->location && $plotting->location !== 'General') {
                        // Use explicitly recorded custom scan location
                        $location = $plotting->location;
                    } else {
                        // Otherwise, inherit their daily supervisor's assigned location
                        $dailySupervisorId = ($plotting && $plotting->supervisor_id) ? $plotting->supervisor_id : $employee->manager_id;
                        $location = ($dailySupervisorId && isset($assignmentMap[$dailySupervisorId][$date])) 
                            ? $assignmentMap[$dailySupervisorId][$date] 
                            : 'General';
                    }
                }

                $row['days'][$date] = [
                    'amount' => $amount,
                    'location' => $location
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

                    if ($isSupervisor) {
                        $assign = SupervisorAssignment::where('supervisor_id', $employee->id)
                            ->where('date', $date)
                            ->first();
                        if ($assign) {
                            $location = $assign->location;
                        }
                    } else {
                        if ($employee->manager_id) {
                            $manager = $employee->manager;
                            $isManagerSupervisor = $manager && $manager->user && $manager->user->role === 2;
                            if ($isManagerSupervisor) {
                                $supervisorId = $employee->manager_id;
                                $assign = SupervisorAssignment::where('supervisor_id', $employee->manager_id)
                                    ->where('date', $date)
                                    ->first();
                                if ($assign) {
                                    $location = $assign->location;
                                }
                            }
                        }
                    }

                    EmployeePlotting::create([
                        'employee_id' => $employee->id,
                        'date' => $date,
                        'supervisor_id' => $supervisorId,
                        'location' => $location,
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
        $dates = [
            '2026-05-18' => 'May 18',
            '2026-05-19' => 'May 19',
            '2026-05-20' => 'May 20',
            '2026-05-21' => 'May 21',
            '2026-05-22' => 'May 22',
        ];

        $plottings = EmployeePlotting::where('employee_id', $employee->id)
            ->whereIn('date', array_keys($dates))
            ->get()
            ->keyBy(function($p) {
                return Carbon::parse($p->date)->format('Y-m-d');
            });

        $assignments = SupervisorAssignment::whereIn('date', array_keys($dates))->get();
        $assignmentMap = [];
        foreach ($assignments as $a) {
            $assignmentMap[$a->supervisor_id][Carbon::parse($a->date)->format('Y-m-d')] = $a->location;
        }

        $weekData = [];
        foreach ($dates as $dateString => $dateLabel) {
            $plotting = $plottings->get($dateString);
            $amount = $plotting ? $plotting->amount : 0.00;

            // Location & Supervisor name resolution
            $location = 'General';
            $supervisorName = 'None';

            $isSupervisor = $employee->user && $employee->user->role === 2;

            if ($isSupervisor) {
                $location = $assignmentMap[$employee->id][$dateString] ?? 'General';
            } else {
                $dailySupervisorId = ($plotting && $plotting->supervisor_id) ? $plotting->supervisor_id : $employee->manager_id;
                
                if ($plotting && $plotting->location && $plotting->location !== 'General') {
                    $location = $plotting->location;
                    if ($plotting->supervisor) {
                        $supervisorName = $plotting->supervisor->first_name . ' ' . $plotting->supervisor->last_name;
                    }
                } else {
                    if ($dailySupervisorId) {
                        $supervisor = Employee::find($dailySupervisorId);
                        if ($supervisor) {
                            $supervisorName = $supervisor->first_name . ' ' . $supervisor->last_name;
                        }
                        if (isset($assignmentMap[$dailySupervisorId][$dateString])) {
                            $location = $assignmentMap[$dailySupervisorId][$dateString];
                        }
                    }
                }
            }

            $weekData[] = [
                'date_string' => $dateString,
                'date' => $dateLabel,
                'workplace' => $location,
                'supervisor' => $supervisorName,
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
                    $assign = SupervisorAssignment::where('supervisor_id', $employee->id)
                        ->where('date', $date)
                        ->first();
                    if ($assign) {
                        $location = $assign->location;
                    }
                } else {
                    if ($employee->manager_id) {
                        $manager = $employee->manager;
                        $isManagerSupervisor = $manager && $manager->user && $manager->user->role === 2;
                        if ($isManagerSupervisor) {
                            $supervisorId = $employee->manager_id;
                            $assign = SupervisorAssignment::where('supervisor_id', $employee->manager_id)
                                ->where('date', $date)
                                ->first();
                            if ($assign) {
                                $location = $assign->location;
                            }
                        }
                    }
                }

                EmployeePlotting::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'supervisor_id' => $supervisorId,
                    'location' => $location,
                    'amount' => $cleanAmount
                ]);
            }
        }

        return redirect()->route('payroll.plotting-payment')->with('status', "Plotting payments for {$employee->first_name} saved successfully.");
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
        $payRun->load('payslips.employee');
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
