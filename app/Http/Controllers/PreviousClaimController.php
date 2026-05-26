<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\PreviousClaim;
use App\Services\PayrollService;
use App\Support\UploadFilename;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PreviousClaimController extends Controller
{
    /**
     * Display the Previous Claims index.
     * - HR (role 4) sees ALL claims.
     * - Employees (role 1) see only their own.
     */
    /**
     * Build the query for Previous Claims based on request parameters.
     */
    private function buildQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = PreviousClaim::with(['employee', 'reviewer', 'payRun'])
            ->latest('created_at');

        // Employees only see their own claims
        if ($user && $user->role === 1 && $user->employee_id) {
            $query->where('employee_id', $user->employee_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $map = ['pending' => 1, 'approved' => 2, 'declined' => 3];
            if (isset($map[$request->status])) {
                $query->where('status', $map[$request->status]);
            }
        }

        // Claim type filter
        if ($request->filled('type')) {
            $query->where('claim_type', $request->type);
        }

        // Date range filters (by claim_date)
        if ($request->filled('start_date')) {
            $query->where('claim_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('claim_date', '<=', $request->end_date);
        }

        // Search filter (SQL level)
        if ($request->filled('q')) {
            $needle = trim($request->q);
            $query->where(function ($subQuery) use ($needle) {
                $subQuery->whereHas('employee', function ($empQuery) use ($needle) {
                    $empQuery->where('first_name', 'like', "%{$needle}%")
                        ->orWhere('last_name', 'like', "%{$needle}%")
                        ->orWhere('middle_name', 'like', "%{$needle}%");
                })
                ->orWhere('claim_type', 'like', "%{$needle}%")
                ->orWhere('description', 'like', "%{$needle}%");
            });
        }

        return $query;
    }

    /**
     * Display the Previous Claims index.
     * - HR (role 4) sees ALL claims.
     * - Employees (role 1) see only their own.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'q'          => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'string', 'in:pending,approved,declined'],
            'type'       => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $filters = [
            'q'          => trim((string) $request->string('q')),
            'status'     => trim((string) $request->string('status')),
            'type'       => trim((string) $request->string('type')),
            'start_date' => trim((string) $request->string('start_date')),
            'end_date'   => trim((string) $request->string('end_date')),
        ];

        $claims = $this->buildQuery($request)->get();

        // Summary stats (for HR)
        $totalPending  = PreviousClaim::where('status', 1)->count();
        $totalApproved = PreviousClaim::where('status', 2)->count();
        $totalDeclined = PreviousClaim::where('status', 3)->count();
        $totalAmount   = PreviousClaim::where('status', 2)->sum('amount');

        // List of open pay runs for the "Assign to Pay Run" modal (HR only)
        $openPayRuns = $user?->role === 4
            ? PayRun::whereIn('status', [1, 2])->orderByDesc('period_start')->get()
            : collect();

        return view('payroll.previous-claims.index', [
            'claims'        => $claims,
            'filters'       => $filters,
            'claimTypes'    => PreviousClaim::claimTypes(),
            'totalPending'  => $totalPending,
            'totalApproved' => $totalApproved,
            'totalDeclined' => $totalDeclined,
            'totalAmount'   => $totalAmount,
            'openPayRuns'   => $openPayRuns,
            'employees'     => $user?->role !== 1
                ? Employee::with('user')->orderBy('first_name')->get()
                : collect(),
        ]);
    }

    /**
     * Export Previous Claims to CSV.
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if ($user->role !== 4) {
            abort(403, 'Unauthorized action. Exports are restricted to HR only.');
        }

        $request->validate([
            'q'          => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'string', 'in:pending,approved,declined'],
            'type'       => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $claims = $this->buildQuery($request)->get();
        $now = Carbon::now()->format('Y-m-d');
        $filename = "previous_claims_export_{$now}.csv";

        $headers = [
            'Claim ID',
            'Employee Code',
            'Employee Name',
            'Claim Type',
            'Claim Date',
            'Amount',
            'Description',
            'Status',
            'Pay Run Period',
            'Reviewed By',
            'Reviewed At',
            'HR Notes'
        ];

        return $this->streamCsv($filename, $headers, function ($file) use ($claims) {
            foreach ($claims as $claim) {
                $employee = $claim->employee;
                $payRun = $claim->payRun;
                $payRunLabel = $payRun 
                    ? $payRun->period_start->format('Y-m-d') . ' - ' . $payRun->period_end->format('Y-m-d') 
                    : 'N/A';

                fputcsv($file, [
                    $claim->id,
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'N/A',
                    $claim->claim_type,
                    $claim->claim_date ? $claim->claim_date->format('Y-m-d') : 'N/A',
                    $claim->amount,
                    $claim->description ?? '',
                    $claim->status_label,
                    $payRunLabel,
                    $claim->reviewer ? $claim->reviewer->name : 'N/A',
                    $claim->reviewed_at ? $claim->reviewed_at->format('Y-m-d H:i') : '',
                    $claim->hr_notes ?? ''
                ]);
            }
        });
    }

    /**
     * Helper method to stream a CSV response.
     */
    private function streamCsv($filename, $headers, $callback)
    {
        $responseHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($headers, $callback) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper encoding support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, $headers);

            $callback($file);

            fclose($file);
        }, 200, $responseHeaders);
    }

    /**
     * Store a new previous claim.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'employee_id'  => ['required', 'exists:employees,id'],
            'claim_type'   => ['required', 'string', 'max:100'],
            'claim_date'   => ['required', 'date', 'before:today'],
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        // Ensure the claim_date is indeed past the current pay period
        // We validate it's before today as a baseline. HR can fine-tune.
        $docPath = null;
        if ($request->hasFile('supporting_document')) {
            $file = $request->file('supporting_document');
            $docPath = $file->storeAs('previous-claims', UploadFilename::build($file, null, 'previous-claims'), 'public');
        }

        PreviousClaim::create([
            'employee_id'         => $validated['employee_id'],
            'claim_type'          => $validated['claim_type'],
            'claim_date'          => $validated['claim_date'],
            'amount'              => $validated['amount'],
            'description'         => $validated['description'] ?? null,
            'supporting_document' => $docPath,
            'status'              => PreviousClaim::STATUS_PENDING,
            'submitted_by'        => $user?->id,
        ]);

        return redirect()->route('payroll.previous-claims.index')
            ->with('success', 'Previous claim submitted successfully and is awaiting HR review.');
    }

    /**
     * Approve a previous claim and optionally assign to a pay run.
     */
    public function approve(Request $request, PreviousClaim $previousClaim): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'hr_notes'   => ['nullable', 'string', 'max:2000'],
            'pay_run_id' => ['nullable', 'exists:pay_runs,id'],
        ]);

        $payRunId = $validated['pay_run_id'] ?? null;

        if (is_null($payRunId)) {
            // Find the earliest active pay run where this employee is selected
            $activePayRun = PayRun::whereIn('status', [1, 2])
                ->whereHas('payslips', function ($q) use ($previousClaim) {
                    $q->where('employee_id', $previousClaim->employee_id);
                })
                ->orderBy('period_start')
                ->first();

            if ($activePayRun) {
                $payRunId = $activePayRun->id;
            }
        }

        $previousClaim->update([
            'status'      => PreviousClaim::STATUS_APPROVED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'hr_notes'    => $validated['hr_notes'] ?? null,
            'pay_run_id'  => $payRunId,
        ]);

        // If assigned to a pay run, regenerate the employee's payslip so the
        // claim amount is immediately included in the payslip breakdown.
        if ($payRunId) {
            $payRun   = PayRun::find($payRunId);
            $employee = $previousClaim->employee;

            if ($payRun && $employee && in_array($payRun->status, [1, 2])) {
                // Delete the stale draft payslip so generatePayslip() can recreate it
                $existing = Payslip::where('pay_run_id', $payRun->id)
                    ->where('employee_id', $employee->id)
                    ->first();

                if ($existing) {
                    $existing->lineItems()->delete();
                    $existing->delete();
                }

                app(PayrollService::class)->generatePayslip($payRun, $employee);
            }
        }

        return redirect()->route('payroll.previous-claims.index')
            ->with('success', 'Claim approved and payslip updated with the claim amount.');
    }

    /**
     * Decline a previous claim.
     */
    public function decline(Request $request, PreviousClaim $previousClaim): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'hr_notes' => ['required', 'string', 'max:2000'],
        ]);

        $previousClaim->update([
            'status'      => PreviousClaim::STATUS_DECLINED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'hr_notes'    => $validated['hr_notes'],
            'pay_run_id'  => null,
        ]);

        return redirect()->route('payroll.previous-claims.index')
            ->with('success', 'Claim has been declined.');
    }

    /**
     * Delete a pending claim (owner or HR only).
     */
    public function destroy(PreviousClaim $previousClaim): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only pending claims can be deleted
        if ($previousClaim->status !== PreviousClaim::STATUS_PENDING) {
            return redirect()->route('payroll.previous-claims.index')
                ->with('error', 'Only pending claims can be deleted.');
        }

        // Delete supporting document if any
        if ($previousClaim->supporting_document) {
            Storage::disk('public')->delete($previousClaim->supporting_document);
        }

        $previousClaim->delete();

        return redirect()->route('payroll.previous-claims.index')
            ->with('success', 'Claim deleted.');
    }
}
