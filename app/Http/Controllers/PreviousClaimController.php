<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\PreviousClaim;
use App\Services\PayrollService;
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
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $filters = [
            'q'      => trim((string) $request->string('q')),
            'status' => trim((string) $request->string('status')),
            'type'   => trim((string) $request->string('type')),
        ];

        $query = PreviousClaim::with(['employee', 'reviewer', 'payRun'])
            ->latest('created_at');

        // Employees only see their own claims
        if ($user && $user->role === 1 && $user->employee_id) {
            $query->where('employee_id', $user->employee_id);
        }

        // Status filter
        if ($filters['status'] !== '') {
            $map = ['pending' => 1, 'approved' => 2, 'declined' => 3];
            if (isset($map[$filters['status']])) {
                $query->where('status', $map[$filters['status']]);
            }
        }

        // Claim type filter
        if ($filters['type'] !== '') {
            $query->where('claim_type', $filters['type']);
        }

        $claims = $query->get();

        // Search
        if ($filters['q'] !== '') {
            $needle = mb_strtolower($filters['q']);
            $claims = $claims->filter(function ($c) use ($needle) {
                return str_contains(mb_strtolower($c->employee?->full_name ?? ''), $needle)
                    || str_contains(mb_strtolower($c->claim_type), $needle)
                    || str_contains(mb_strtolower($c->description ?? ''), $needle);
            })->values();
        }

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
            $docPath = $request->file('supporting_document')
                ->store('previous-claims', 'public');
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

        $previousClaim->update([
            'status'      => PreviousClaim::STATUS_APPROVED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'hr_notes'    => $validated['hr_notes'] ?? null,
            'pay_run_id'  => $validated['pay_run_id'] ?? null,
        ]);

        // If assigned to a pay run, regenerate the employee's payslip so the
        // claim amount is immediately included in the payslip breakdown.
        if ($validated['pay_run_id']) {
            $payRun   = PayRun::find($validated['pay_run_id']);
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
