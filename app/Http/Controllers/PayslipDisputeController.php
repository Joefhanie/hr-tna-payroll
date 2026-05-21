<?php

namespace App\Http\Controllers;

use App\Models\PayslipDispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayslipDisputeController extends Controller
{
    /**
     * Display a listing of the disputes.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = PayslipDispute::with(['employee', 'payslip.payRun', 'lineItem', 'resolver'])->latest('created_at');

        // Employees only see their own
        if ($user && $user->role === 1 && $user->employee_id) {
            $query->where('employee_id', $user->employee_id);
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
            'totalPending'  => $totalPending,
            'totalResolved' => $totalResolved,
            'totalRejected' => $totalRejected,
            'employees'     => $employees,
            'payslips'      => $payslips,
        ]);
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
        if (Auth::user()->role !== 4) abort(403);

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
