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
        // PayRun Frequency: 1=Weekly, 2=Bi-weekly, 3=Semi-monthly, 4=Monthly
        $payRun = PayRun::create([
            'name' => $periodStart->format('F Y'),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'pay_date' => $periodEnd->toDateString(),
            'frequency' => 4,  // Monthly
            'status' => 2,     // Processing
            'created_by' => $request->user()->id ?? null,
        ]);

        $payrollService->finalizePayRun($payRun);

        return redirect()->route('payroll.index')->with('status', 'Payroll run created and finalized.');
    }
}
