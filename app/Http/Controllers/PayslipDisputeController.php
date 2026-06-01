<?php

namespace App\Http\Controllers;

use App\Models\PayslipDispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PayslipDisputeController extends Controller
{
    /**
     * Display a listing of the disputes.
     */
    /**
     * Build the query for Disputes based on request parameters.
     */
    private function buildQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = PayslipDispute::with(['employee', 'payslip.payRun', 'lineItem', 'resolver'])
            ->latest('created_at');

        // Employees only see their own
        if ($user && $user->role === 1 && $user->employee_id) {
            $query->where('employee_id', $user->employee_id);
        }

        // Status filter (1 = Pending, 2 = Resolved, 3 = Rejected)
        if ($request->filled('status')) {
            $map = ['pending' => 1, 'resolved' => 2, 'rejected' => 3];
            if (isset($map[$request->status])) {
                $query->where('status', $map[$request->status]);
            }
        }

        // Date range filters (by created_at)
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
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
                ->orWhere('dispute_reason', 'like', "%{$needle}%");
            });
        }

        return $query;
    }

    /**
     * Display a listing of the disputes.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'q'          => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'string', 'in:pending,resolved,rejected'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $filters = [
            'q'          => trim((string) $request->string('q')),
            'status'     => trim((string) $request->string('status')),
            'start_date' => trim((string) $request->string('start_date')),
            'end_date'   => trim((string) $request->string('end_date')),
        ];

        $query = $this->buildQuery($request);

        // Employees only see their own
        if ($user && $user->role === 1 && $user->employee_id) {
            $payslips = \App\Models\Payslip::with('payRun')
                ->whereHas('payRun', function($q) {
                    $q->whereNotIn('status', [1, 2, 13]);
                })
                ->where('employee_id', $user->employee_id)
                ->latest()
                ->take(12)
                ->get();
            $employees = [];
        } else {
            $employees = \App\Models\Employee::orderBy('first_name')->get();
            $payslips = [];
        }

        $disputes = $query->get();

        // Summary stats (for HR)
        $totalPending  = PayslipDispute::where('status', 1)->count();
        $totalResolved = PayslipDispute::where('status', 2)->count();
        $totalRejected = PayslipDispute::where('status', 3)->count();

        return view('payroll.disputes.index', [
            'disputes'      => $disputes,
            'filters'       => $filters,
            'totalPending'  => $totalPending,
            'totalResolved' => $totalResolved,
            'totalRejected' => $totalRejected,
            'employees'     => $employees,
            'payslips'      => $payslips,
        ]);
    }

    /**
     * Export Payslip Disputes to CSV.
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
            'status'     => ['nullable', 'string', 'in:pending,resolved,rejected'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ]);

        $disputes = $this->buildQuery($request)->get();
        $now = Carbon::now()->format('Y-m-d');
        $filename = "payslip_disputes_export_{$now}.csv";

        $headers = [
            'Dispute ID',
            'Employee Code',
            'Employee Name',
            'Pay Run Period',
            'Dispute Amount',
            'Line Item',
            'Reason',
            'Status',
            'Resolved By',
            'Resolved At',
            'HR Notes'
        ];

        $statusLabels = [
            1 => 'Pending',
            2 => 'Resolved',
            3 => 'Rejected'
        ];

        return $this->streamCsv($filename, $headers, function ($file) use ($disputes, $statusLabels) {
            foreach ($disputes as $dispute) {
                $employee = $dispute->employee;
                $payslip = $dispute->payslip;
                $payRun = $payslip?->payRun;
                $payRunPeriod = $payRun 
                    ? $payRun->period_start->format('Y-m-d') . ' - ' . $payRun->period_end->format('Y-m-d') 
                    : 'N/A';

                fputcsv($file, [
                    $dispute->id,
                    $employee->employee_code ?? 'N/A',
                    $employee ? $employee->full_name : 'N/A',
                    $payRunPeriod,
                    $dispute->dispute_amount,
                    $dispute->lineItem ? $dispute->lineItem->description : 'Entire Payslip',
                    $dispute->dispute_reason,
                    $statusLabels[$dispute->status] ?? 'Unknown',
                    $dispute->resolver ? $dispute->resolver->name : 'N/A',
                    $dispute->resolved_at ? $dispute->resolved_at->format('Y-m-d H:i') : '',
                    $dispute->hr_notes ?? ''
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
     * Store a new dispute.
     */
    public function store(Request $request)
    {
        $request->validate([
            'payslip_id'           => 'required|exists:payslips,id',
            'payslip_line_item_id' => 'nullable|exists:payslip_line_items,id',
            'dispute_amount'       => 'required|numeric|min:0',
            'dispute_reason'       => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        
        // Determine employee_id based on role
        if ($user->role === 4) {
            $request->validate(['employee_id' => 'required|exists:employees,id']);
            $employeeId = $request->input('employee_id');
        } else {
            $employeeId = $user->employee_id;
        }

        PayslipDispute::create([
            'employee_id'          => $employeeId,
            'payslip_id'           => $request->input('payslip_id'),
            'payslip_line_item_id' => $request->input('payslip_line_item_id'),
            'dispute_amount'       => $request->input('dispute_amount'),
            'dispute_reason'       => $request->input('dispute_reason'),
            'status'               => 1, // Pending
        ]);

        return redirect()->back()->with('success', 'Dispute filed successfully.');
    }

    /**
     * Resolve a dispute.
     */
    public function resolve(Request $request, PayslipDispute $dispute)
    {
        if (Auth::user()->role !== 4) abort(403);

        $dispute->update([
            'status'      => 2,
            'hr_notes'    => $request->input('hr_notes'),
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        // Find the earliest active pay run where this employee is selected
        $activePayRun = \App\Models\PayRun::whereIn('status', [1, 2])
            ->whereHas('payslips', function ($q) use ($dispute) {
                $q->where('employee_id', $dispute->employee_id);
            })
            ->orderBy('period_start')
            ->first();

        if ($activePayRun) {
            // Find the draft payslip for this employee in that pay run
            $draftPayslip = \App\Models\Payslip::where('pay_run_id', $activePayRun->id)
                ->where('employee_id', $dispute->employee_id)
                ->first();

            if ($draftPayslip) {
                $dispute->update([
                    'adjustment_pay_run_id' => $activePayRun->id,
                    'adjustment_payslip_id' => $draftPayslip->id,
                ]);

                // Regenerate the draft payslip
                $draftPayslip->lineItems()->delete();
                $draftPayslip->delete();

                $payrollService = app(\App\Services\PayrollService::class);
                $payrollService->generatePayslip($activePayRun, $dispute->employee);
            }
        }

        return redirect()->back()->with('success', 'Dispute marked as resolved.');
    }

    /**
     * Reject a dispute.
     */
    public function reject(Request $request, PayslipDispute $dispute)
    {
        if (Auth::user()->role !== 4) abort(403);

        $request->validate(['hr_notes' => 'required|string|max:1000']);

        $dispute->update([
            'status'      => 3,
            'hr_notes'    => $request->input('hr_notes'),
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Dispute marked as rejected.');
    }

    /**
     * Get payslips for an employee (AJAX for HR).
     */
    public function getPayslips($employeeId)
    {
        try {
            if (Auth::user()->role !== 4) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $payslips = \App\Models\Payslip::with('payRun')
                ->whereHas('payRun', function($q) {
                    $q->whereNotIn('status', [1, 2, 13]);
                })
                ->where('employee_id', $employeeId)
                ->latest()
                ->take(12)
                ->get();
                
            $data = $payslips->map(function ($ps) {
                return [
                    'id' => $ps->id,
                    'name' => $ps->payRun ? $ps->payRun->name . ' (' . \Carbon\Carbon::parse($ps->payRun->period_start)->format('M d') . ' - ' . \Carbon\Carbon::parse($ps->payRun->period_end)->format('M d, Y') . ')' : 'Unknown Pay Run',
                ];
            });

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('getPayslips error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get line items for a specific payslip (AJAX).
     */
    public function getPayslipItems(Request $request, $payslipId)
    {
        $payslip = \App\Models\Payslip::with('lineItems')->findOrFail($payslipId);

        // Security check: HR or the owner
        if (Auth::user()->role !== 4 && Auth::user()->employee_id !== $payslip->employee_id) {
            abort(403);
        }

        $items = $payslip->lineItems->map(function ($item) {
            return [
                'id'          => $item->id,
                'description' => $item->description,
                'amount'      => $item->amount,
                'type'        => $item->component_type === 1 ? 'Earning' : 'Deduction'
            ];
        });

        return response()->json([
            'gross_pay' => $payslip->gross_pay,
            'net_pay'   => $payslip->net_pay,
            'items'     => $items
        ]);
    }
}
