<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\SupervisorAssignment;
use App\Models\EmployeePlotting;
use App\Models\TaxBracket;
use App\Models\GovernmentContributionRate;
use App\Models\DeductionRule;
use App\Models\SalaryRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Disable Foreign Keys & Truncate existing tables
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        User::truncate();
        Employee::truncate();
        Department::truncate();
        Position::truncate();
        SupervisorAssignment::truncate();
        EmployeePlotting::truncate();
        TaxBracket::truncate();
        GovernmentContributionRate::truncate();
        DeductionRule::truncate();
        SalaryRecord::truncate();
        DB::table('sessions')->truncate();
        DB::table('employee_tax_bracket')->truncate();
        DB::table('employee_government_contribution')->truncate();
        DB::table('employee_deduction_rule')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        // 1b. Seed Default Salary Settings
        $taxBrackets = [
            ['threshold' => 0.00, 'rate' => 0.00, 'label' => 'Exempt', 'notes' => '₱20,833 and below', 'is_active' => true, 'sort_order' => 0],
            ['threshold' => 20833.00, 'rate' => 0.15, 'label' => 'Bracket 2', 'notes' => 'Over ₱20,833 to ₱33,333', 'is_active' => true, 'sort_order' => 1],
            ['threshold' => 33333.00, 'rate' => 0.20, 'label' => 'Bracket 3', 'notes' => 'Over ₱33,333 to ₱66,667', 'is_active' => true, 'sort_order' => 2],
            ['threshold' => 66667.00, 'rate' => 0.25, 'label' => 'Bracket 4', 'notes' => 'Over ₱66,667 to ₱166,667', 'is_active' => true, 'sort_order' => 3],
            ['threshold' => 166667.00, 'rate' => 0.30, 'label' => 'Bracket 5', 'notes' => 'Over ₱166,667 to ₱666,667', 'is_active' => true, 'sort_order' => 4],
            ['threshold' => 666667.00, 'rate' => 0.35, 'label' => 'Bracket 6', 'notes' => 'Over ₱666,667', 'is_active' => true, 'sort_order' => 5],
        ];
        foreach ($taxBrackets as $tb) {
            TaxBracket::create($tb);
        }

        $govContributions = [
            ['name' => 'SSS', 'employee_rate' => 0.045, 'employer_rate' => 0.095, 'is_active' => true, 'sort_order' => 0],
            ['name' => 'PhilHealth', 'employee_rate' => 0.025, 'employer_rate' => 0.025, 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Pag-IBIG', 'employee_rate' => 0.020, 'employer_rate' => 0.020, 'is_active' => true, 'sort_order' => 2],
        ];
        foreach ($govContributions as $gc) {
            GovernmentContributionRate::create($gc);
        }

        $deductions = [
            ['name' => 'Late Deduction', 'type' => 'Prorated', 'amount' => null, 'rate' => 0.001, 'scope' => 'Attendance linked', 'is_active' => true, 'sort_order' => 0],
            ['name' => 'Absent Deduction', 'type' => 'Fixed', 'amount' => 1000.00, 'rate' => null, 'scope' => 'Attendance linked', 'is_active' => true, 'sort_order' => 1],
        ];
        foreach ($deductions as $d) {
            DeductionRule::create($d);
        }

        // Seed company-wide attendance defaults
        \App\Models\PayrollSetting::truncate();
        \App\Models\PayrollSetting::create([
            'attendance_overtime_multiplier' => 1.25,
            'attendance_night_differential_multiplier' => 0.10,
            'attendance_late_deduction_multiplier' => 1.00,
            'attendance_undertime_deduction_multiplier' => 1.00,
            'attendance_absence_deduction_multiplier' => 1.00,
        ]);

        // 2. Seed Departments
        $deptHr = Department::create(['name' => 'Human Resources']);
        $deptOps = Department::create(['name' => 'Operations']);
        $deptIt = Department::create(['name' => 'Information Technology']);

        // 3. Seed Positions
        $posHrMgr = Position::create([
            'title' => 'HR Manager',
            'level' => 'Manager',
            'department_id' => $deptHr->id,
            'min_salary' => 50000.00,
            'max_salary' => 90000.00,
        ]);

        $posOpsSv = Position::create([
            'title' => 'Operations Supervisor',
            'level' => 'Manager',
            'department_id' => $deptOps->id,
            'min_salary' => 40000.00,
            'max_salary' => 70000.00,
        ]);

        $posSupportSv = Position::create([
            'title' => 'Customer Support Supervisor',
            'level' => 'Manager',
            'department_id' => $deptOps->id,
            'min_salary' => 40000.00,
            'max_salary' => 70000.00,
        ]);

        $posTechSv = Position::create([
            'title' => 'Technical Lead',
            'level' => 'Lead',
            'department_id' => $deptIt->id,
            'min_salary' => 45000.00,
            'max_salary' => 80000.00,
        ]);

        $posOpsAssoc = Position::create([
            'title' => 'Operations Associate',
            'level' => 'Junior',
            'department_id' => $deptOps->id,
            'min_salary' => 20000.00,
            'max_salary' => 35000.00,
        ]);

        $posSupportAssoc = Position::create([
            'title' => 'Support Associate',
            'level' => 'Junior',
            'department_id' => $deptOps->id,
            'min_salary' => 20000.00,
            'max_salary' => 35000.00,
        ]);

        $posDevAssoc = Position::create([
            'title' => 'Software Engineer',
            'level' => 'Junior',
            'department_id' => $deptIt->id,
            'min_salary' => 25000.00,
            'max_salary' => 45000.00,
        ]);

        // Helper function to create an Employee and then assign code based on its database ID
        $createEmployeeWithInitialsCode = function ($attributes) {
            $attributes['employee_code'] = 'TMP' . str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $emp = Employee::create($attributes);

            $firstInitial = strtoupper(substr(trim($emp->first_name), 0, 1));
            $lastInitial = strtoupper(substr(trim($emp->last_name), 0, 1));
            $code = $firstInitial . $lastInitial . str_pad((string) $emp->id, 3, '0', STR_PAD_LEFT);

            $emp->update(['employee_code' => $code]);

            // Add active SalaryRecord
            $position = Position::find($emp->position_id);
            $salaryAmount = 30000.00;
            if ($position) {
                $salaryAmount = $position->min_salary + (($position->max_salary - $position->min_salary) / 2);
            }
            SalaryRecord::create([
                'employee_id' => $emp->id,
                'amount' => $salaryAmount,
                'currency' => 'PHP',
                'pay_frequency' => 4, // Monthly
                'effective_date' => $emp->hire_date ?? now()->toDateString(),
                'reason' => 'Initial Salary',
            ]);

            // Sync default tax, contribution and deduction settings
            $emp->taxBrackets()->sync(TaxBracket::pluck('id')->all());
            $emp->governmentContributionRates()->sync(GovernmentContributionRate::pluck('id')->all());
            $emp->deductionRules()->sync(DeductionRule::pluck('id')->all());

            return $emp;
        };

        // Standard Password for all accounts
        $hashedPassword = Hash::make('password123');

        // 4. Seed 1 HR
        $hrEmp = $createEmployeeWithInitialsCode([
            'first_name' => 'Joefhanie',
            'last_name' => 'Perez',
            'middle_name' => 'Cruz',
            'email' => 'hr@example.com',
            'phone' => '+63 917 123 4567',
            'birth_date' => '1988-05-12',
            'gender' => 'Female',
            'nationality' => 'Filipino',
            'marital_status' => 'Single',
            'address_line1' => '888 Taft Ave',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => '1000',
            'country' => 'Philippines',
            'status' => 1, // Active
            'employment_type' => 1, // Full-time
            'hire_date' => '2020-01-15',
            'position_id' => $posHrMgr->id,
            'department_id' => $deptHr->id,
        ]);

        User::create([
            'name' => 'Joefhanie Diaz',
            'username' => 'hr_admin',
            'email' => 'hr@example.com',
            'password' => $hashedPassword,
            'employee_id' => $hrEmp->id,
            'role' => 4, // HR
            'status' => 1,
        ]);

        // 5. Seed 3 Supervisors (SV)
        $svData = [
            [
                'first_name' => 'Andrei',
                'last_name' => 'Dilag',
                'email' => 'sv1@example.com',
                'username' => 'sv_ops',
                'position' => $posOpsSv,
                'dept' => $deptOps,
            ],
            [
                'first_name' => 'Ramon',
                'last_name' => 'Valenzuela',
                'email' => 'sv2@example.com',
                'username' => 'sv_support',
                'position' => $posSupportSv,
                'dept' => $deptOps,
            ],
            [
                'first_name' => 'Maria Clara',
                'last_name' => 'Santos',
                'email' => 'sv3@example.com',
                'username' => 'sv_tech',
                'position' => $posTechSv,
                'dept' => $deptIt,
            ]
        ];

        $svEmployees = [];
        foreach ($svData as $idx => $sv) {
            $svEmp = $createEmployeeWithInitialsCode([
                'first_name' => $sv['first_name'],
                'last_name' => $sv['last_name'],
                'email' => $sv['email'],
                'phone' => '+63 917 222 000' . ($idx + 1),
                'birth_date' => '1985-08-20',
                'gender' => ($idx == 2) ? 'Female' : 'Male',
                'nationality' => 'Filipino',
                'marital_status' => 'Married',
                'address_line1' => 'Supervisor St',
                'city' => 'Makati',
                'province' => 'Metro Manila',
                'postal_code' => '1200',
                'status' => 1,
                'employment_type' => 1,
                'hire_date' => '2021-06-10',
                'position_id' => $sv['position']->id,
                'department_id' => $sv['dept']->id,
                'manager_id' => $hrEmp->id, // Reports to HR/Manager
            ]);

            User::create([
                'name' => $sv['first_name'] . ' ' . $sv['last_name'],
                'username' => $sv['username'],
                'email' => $sv['email'],
                'password' => $hashedPassword,
                'employee_id' => $svEmp->id,
                'role' => 2, // Supervisor
                'status' => 1,
            ]);

            $svEmployees[] = $svEmp;
        }

        // 6. Seed 6 Employees (EMP) — "2 emp for each 1 SV"
        $empData = [
            // Under SV 1 (Andrei Dilag)
            [
                'first_name' => 'Juan',
                'last_name' => 'dela Cruz',
                'email' => 'emp1@example.com',
                'username' => 'emp_ops_a',
                'position' => $posOpsAssoc,
                'dept' => $deptOps,
                'manager' => $svEmployees[0],
            ],
            [
                'first_name' => 'Princess',
                'last_name' => 'Mendoza',
                'email' => 'emp2@example.com',
                'username' => 'emp_ops_b',
                'position' => $posOpsAssoc,
                'dept' => $deptOps,
                'manager' => $svEmployees[0],
            ],
            // Under SV 2 (Ramon Valenzuela)
            [
                'first_name' => 'Jose Rizal',
                'last_name' => 'Macaraeg',
                'email' => 'emp3@example.com',
                'username' => 'emp_support_a',
                'position' => $posSupportAssoc,
                'dept' => $deptOps,
                'manager' => $svEmployees[1],
            ],
            [
                'first_name' => 'Arnel',
                'last_name' => 'Pineda',
                'email' => 'emp4@example.com',
                'username' => 'emp_support_b',
                'position' => $posSupportAssoc,
                'dept' => $deptOps,
                'manager' => $svEmployees[1],
            ],
            // Under SV 3 (Maria Clara Santos)
            [
                'first_name' => 'Gloc Nine',
                'last_name' => 'Alimario',
                'email' => 'emp5@example.com',
                'username' => 'emp_tech_a',
                'position' => $posDevAssoc,
                'dept' => $deptIt,
                'manager' => $svEmployees[2],
            ],
            [
                'first_name' => 'Catriona',
                'last_name' => 'Gray',
                'email' => 'emp6@example.com',
                'username' => 'emp_tech_b',
                'position' => $posDevAssoc,
                'dept' => $deptIt,
                'manager' => $svEmployees[2],
            ],
        ];

        $employees = [];
        foreach ($empData as $idx => $emp) {
            $empRecord = $createEmployeeWithInitialsCode([
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'email' => $emp['email'],
                'phone' => '+63 917 333 000' . ($idx + 1),
                'birth_date' => '1995-10-05',
                'gender' => ($idx % 2 == 0) ? 'Male' : 'Female',
                'nationality' => 'Filipino',
                'marital_status' => 'Single',
                'address_line1' => 'Employee Rd',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'postal_code' => '1100',
                'status' => 1,
                'employment_type' => 1,
                'hire_date' => '2023-01-10',
                'position_id' => $emp['position']->id,
                'department_id' => $emp['dept']->id,
                'manager_id' => $emp['manager']->id, // Reports to specific SV!
            ]);

            $employees[] = $empRecord;

            User::create([
                'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                'username' => $emp['username'],
                'email' => $emp['email'],
                'password' => $hashedPassword,
                'employee_id' => $empRecord->id,
                'role' => 1, // Employee
                'status' => 1,
            ]);
        }

        // 7. Seed Plotting Data for the week of May 18 - May 22, 2026
        $dates = ['2026-05-18', '2026-05-19', '2026-05-20', '2026-05-21', '2026-05-22'];

        // Supervisor location assignments for this week
        $svLocations = [
            $svEmployees[0]->id => [
                '2026-05-18' => 'Manila Zoo',
                '2026-05-19' => 'Manila Zoo',
                '2026-05-20' => 'SM',
                '2026-05-21' => 'Manila Zoo',
                '2026-05-22' => 'Manila Zoo',
            ],
            $svEmployees[1]->id => [
                '2026-05-18' => 'Robinsons Mall',
                '2026-05-19' => 'Robinsons Mall',
                '2026-05-20' => 'SM',
                '2026-05-21' => 'Robinsons Mall',
                '2026-05-22' => 'Robinsons Mall',
            ],
            $svEmployees[2]->id => [
                '2026-05-18' => 'IT Park',
                '2026-05-19' => 'IT Park',
                '2026-05-20' => 'SM',
                '2026-05-21' => 'IT Park',
                '2026-05-22' => 'IT Park',
            ]
        ];

        // Create Supervisor Assignments
        foreach ($svLocations as $svId => $locs) {
            foreach ($locs as $date => $loc) {
                SupervisorAssignment::create([
                    'supervisor_id' => $svId,
                    'location' => $loc,
                    'date' => $date
                ]);
            }
        }

        // Create Employee Plottings (QR Scans + Plotted Payments)
        // Let's seed varying amounts to make it realistic
        $baseAmounts = [500.00, 600.00, 450.00, 550.00, 700.00, 650.00];

        foreach ($employees as $empIdx => $emp) {
            foreach ($dates as $dateIdx => $date) {
                $svId = $emp->manager_id;

                // Special case: Princess Mendoza (empIdx == 1) works under Ramon Valenzuela (svEmployees[1]->id) on May 19
                if ($empIdx === 1 && $date === '2026-05-19') {
                    $svId = $svEmployees[1]->id;
                }

                $location = $svLocations[$svId][$date] ?? 'General';

                // Some days might not have time-ins (e.g. absent/rest day)
                // Let's make John Doe (empIdx 0) have time-ins on all days, Charlie Green (empIdx 4) absent on Wednesday (dateIdx 2), etc.
                if ($empIdx == 4 && $dateIdx == 2) {
                    continue; // Skip seeding Charlie Green's time-in for Wednesday
                }

                // Plotted Payment amount is typically around their base salary rate
                // Let's set some default amounts
                $amount = $baseAmounts[$empIdx] + (float) rand(-50, 50);

                EmployeePlotting::create([
                    'employee_id' => $emp->id,
                    'supervisor_id' => $svId,
                    'date' => $date,
                    'location' => $location,
                    'amount' => $amount
                ]);
            }
        }
    }
}
