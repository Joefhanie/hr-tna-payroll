<?php

namespace App\Http\Controllers;

use App\Models\BenefitPlan;
use App\Models\BenefitEnrollment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BenefitsController extends Controller
{
    /**
     * Display a listing of the benefit plans.
     */
    public function index(Request $request)
    {
        // Auto-seed default plans if empty
        if (Schema::hasTable('benefit_plans') && BenefitPlan::count() === 0) {
            $defaultPlans = [
                [
                    'name' => 'HMO – Maxicare Platinum',
                    'benefit_type' => 'Health',
                    'provider' => 'Maxicare',
                    'employee_cost' => 4200.00,
                    'employer_cost' => 0.00,
                    'coverage_details' => 'Comprehensive health plan including hospitalization, outpatient care, and emergency services.',
                    'is_active' => 1
                ],
                [
                    'name' => 'Group Life Insurance',
                    'benefit_type' => 'Insurance',
                    'provider' => 'Sun Life',
                    'employee_cost' => 650.00,
                    'employer_cost' => 0.00,
                    'coverage_details' => 'Life insurance coverage for accidental death, disability, and critical illness.',
                    'is_active' => 1
                ],
                [
                    'name' => 'SSS / PhilHealth / Pag-IBIG',
                    'benefit_type' => 'Government',
                    'provider' => 'Government',
                    'employee_cost' => 0.00,
                    'employer_cost' => 0.00,
                    'coverage_details' => 'Statutory government contributions and mandatory benefits.',
                    'is_active' => 1
                ],
                [
                    'name' => 'Dental Plan',
                    'benefit_type' => 'Health',
                    'provider' => 'Metro Dental',
                    'employee_cost' => 500.00,
                    'employer_cost' => 0.00,
                    'coverage_details' => 'Free annual dental cleaning, fillings, and dental consultations.',
                    'is_active' => 1
                ],
                [
                    'name' => 'Wellness Allowance',
                    'benefit_type' => 'Allowance',
                    'provider' => 'Company Gym & Wellness',
                    'employee_cost' => 1000.00,
                    'employer_cost' => 0.00,
                    'coverage_details' => 'Monthly allowance for gym membership, fitness classes, or health products.',
                    'is_active' => 1
                ]
            ];

            foreach ($defaultPlans as $p) {
                $plan = BenefitPlan::create($p);
                
                // Seed enrollments for active employees
                $employees = Employee::all();
                if ($employees->isNotEmpty()) {
                    if ($plan->name === 'Group Life Insurance' || $plan->name === 'SSS / PhilHealth / Pag-IBIG' || $plan->name === 'Wellness Allowance') {
                        // Enroll all
                        foreach ($employees as $emp) {
                            BenefitEnrollment::create([
                                'employee_id' => $emp->id,
                                'plan_id' => $plan->id,
                                'enrollment_date' => now()->subMonths(6),
                                'coverage_start' => now()->subMonths(6),
                                'status' => 1, // Active
                            ]);
                        }
                    } elseif ($plan->name === 'HMO – Maxicare Platinum') {
                        // Enroll all except 1
                        foreach ($employees->take($employees->count() - 1) as $emp) {
                            BenefitEnrollment::create([
                                'employee_id' => $emp->id,
                                'plan_id' => $plan->id,
                                'enrollment_date' => now()->subMonths(6),
                                'coverage_start' => now()->subMonths(6),
                                'status' => 1,
                            ]);
                        }
                    } elseif ($plan->name === 'Dental Plan') {
                        // Enroll 60%
                        foreach ($employees->take(ceil($employees->count() * 0.6)) as $emp) {
                            BenefitEnrollment::create([
                                'employee_id' => $emp->id,
                                'plan_id' => $plan->id,
                                'enrollment_date' => now()->subMonths(6),
                                'coverage_start' => now()->subMonths(6),
                                'status' => 1,
                            ]);
                        }
                    }
                }
            }
        }

        // Fetch plans with the active enrollments count
        $plans = BenefitPlan::withCount(['enrollments as enrolled_count' => function ($q) {
            $q->where('status', 1);
        }])->get();

        return view('benefits', compact('plans'));
    }

    /**
     * Store a newly created benefit plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'benefit_type' => 'required|string|max:80',
            'provider' => 'nullable|string|max:120',
            'employee_cost' => 'nullable|numeric|min:0',
            'employer_cost' => 'nullable|numeric|min:0',
            'coverage_details' => 'nullable|string',
        ]);

        $validated['is_active'] = 1;

        BenefitPlan::create($validated);

        return redirect()->route('benefits')
            ->with('success', 'Benefit plan created successfully.');
    }

    /**
     * Display a specific benefit plan for management.
     */
    public function show(BenefitPlan $plan)
    {
        // Enrolled employees
        $enrolled = Employee::whereHas('benefitEnrollments', function ($q) use ($plan) {
            $q->where('plan_id', $plan->id)->where('status', 1);
        })->with(['position', 'department'])->get();

        // Not enrolled employees
        $notEnrolled = Employee::whereDoesntHave('benefitEnrollments', function ($q) use ($plan) {
            $q->where('plan_id', $plan->id)->where('status', 1);
        })->with(['position', 'department'])->get();

        return view('benefits.show', compact('plan', 'enrolled', 'notEnrolled'));
    }

    /**
     * Update the details of a specific benefit plan.
     */
    public function update(Request $request, BenefitPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'benefit_type' => 'required|string|max:80',
            'provider' => 'nullable|string|max:120',
            'employee_cost' => 'nullable|numeric|min:0',
            'employer_cost' => 'nullable|numeric|min:0',
            'coverage_details' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $plan->update($validated);

        return redirect()->route('benefits.show', $plan->id)
            ->with('success', 'Benefit plan updated successfully.');
    }

    /**
     * Delete a specific benefit plan.
     */
    public function destroy(BenefitPlan $plan): RedirectResponse
    {
        // Remove all enrollments for this plan first
        BenefitEnrollment::where('plan_id', $plan->id)->delete();
        $plan->delete();

        return redirect()->route('benefits')
            ->with('success', 'Benefit plan deleted successfully.');
    }

    /**
     * Enroll an employee in a specific plan.
     */
    public function enroll(Request $request, BenefitPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'enrollment_date' => 'required|date',
            'coverage_start' => 'required|date',
        ]);

        // Check if already enrolled active
        $existing = BenefitEnrollment::where('employee_id', $validated['employee_id'])
            ->where('plan_id', $plan->id)
            ->where('status', 1)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Employee is already enrolled in this plan.');
        }

        BenefitEnrollment::create([
            'employee_id' => $validated['employee_id'],
            'plan_id' => $plan->id,
            'enrollment_date' => $validated['enrollment_date'],
            'coverage_start' => $validated['coverage_start'],
            'status' => 1, // Active
            'enrolled_by' => Auth::id(),
        ]);

        return redirect()->route('benefits.show', $plan->id)
            ->with('success', 'Employee enrolled successfully.');
    }

    /**
     * Disenroll/remove an employee from a specific plan.
     */
    public function disenroll(BenefitPlan $plan, Employee $employee): RedirectResponse
    {
        // Delete or Terminate the enrollment
        // Since the schema has coverage_end, we can update it or just delete the row.
        // Let's set the status to 2 (Terminated) and coverage_end to today to preserve history, or delete it.
        // To be safe and clean, let's update status = 2 and coverage_end = today.
        $enrollment = BenefitEnrollment::where('employee_id', $employee->id)
            ->where('plan_id', $plan->id)
            ->where('status', 1)
            ->first();

        if ($enrollment) {
            $enrollment->update([
                'status' => 2, // Terminated
                'coverage_end' => now()->toDateString(),
            ]);
            return redirect()->route('benefits.show', $plan->id)
                ->with('success', 'Employee disenrolled successfully.');
        }

        return redirect()->route('benefits.show', $plan->id)
            ->with('error', 'Enrollment not found.');
    }
}
