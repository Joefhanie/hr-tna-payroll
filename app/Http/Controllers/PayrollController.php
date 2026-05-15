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

        // Get all pay runs for the current year
        $payRuns = PayRun::whereYear('period_start', $currentYear)
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
     * Run payroll for the current period (simple default: full current month).
     */
    public function run(Request $request, PayrollService $payrollService)
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // PayRun Status: 1=Draft, 2=Processing, 3=Completed, 4=Cancelled
        // PayRun Frequency: 1=Hourly, 2=Daily, 3=Weekly, 4=Bi-weekly, 5=Monthly, 6=Annual

        $payRun = PayRun::create([
            'name' => $periodStart->format('F Y'),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'pay_date' => $periodEnd->toDateString(),
            'frequency' => 4,  // Monthly
            'status' => 2,     // Processing / Draft Review
            'created_by' => $request->user()->id ?? null,
        ]);

        $payrollService->generateDraftPayRun($payRun);

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
    public function edit(PayRun $payRun): View
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
