<?php

namespace App\Http\Controllers;

use App\Models\PayRun;
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
     * Show plotting details for a single employee.
     */
    public function showPlottingEmployee(string $employee): View
    {
        $employeeName = urldecode($employee);

        $weekData = [
            ['date' => 'May 18', 'workplace' => 'Manila Zoo'],
            ['date' => 'May 19', 'workplace' => 'Manila Zoo'],
            ['date' => 'May 20', 'workplace' => 'SM'],
            ['date' => 'May 21', 'workplace' => 'Manila Zoo'],
            ['date' => 'May 22', 'workplace' => 'Manila Zoo'],
        ];

        return view('payroll.per-employee', compact('employeeName', 'weekData'));
    }

    public function showWorkLocationDetails(string $date, string $workplace): View
    {
        $workplaceName = urldecode($workplace);

        // Sample employee data for the work location
        $employeeData = [
            ['name' => 'Kenneth', 'supervisor' => 'Andre', 'amount' => ''],
            ['name' => 'Alfren', 'supervisor' => 'Andre', 'amount' => ''],
            ['name' => 'Jano', 'supervisor' => 'Jim', 'amount' => ''],
            ['name' => 'KJ', 'supervisor' => 'Jim', 'amount' => ''],
        ];

        return view('payroll.work-location-details', compact('date', 'workplaceName', 'employeeData'));
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
