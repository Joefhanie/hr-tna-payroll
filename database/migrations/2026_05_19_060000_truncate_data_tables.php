<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Truncates all data tables except:
     * - Configuration tables (tax_brackets, government_contributions, government_contribution_rates, payroll_settings)
     * - Shift tables (shifts, shift_assignments)
     * - Late deduction table (late_deductions)
     * - Deduction configuration tables
     */
    public function up(): void
    {
        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Tables to truncate (data tables)
        $tablesToTruncate = [
            'users',
            'sessions',
            'employees',
            'departments',
            'positions',
            'emergency_contacts',
            'government_ids',
            'salary_records',
            'employee_documents',
            'time_logs',
            'break_logs',
            'timesheets',
            'leave_requests',
            'leaves',
            'payslips',
            'payslip_line_items',
            'pay_runs',
            'benefit_enrollments',
            'employee_onboarding',
            'onboarding_task_status',
            'supervisor_assignments',
            'employee_plotting',
            'attendance',
            'notifications',
            'portal_activity_logs',
            'workforce_snapshots',
            'audit_logs',
            'reimbursement_requests',
        ];

        foreach ($tablesToTruncate as $table) {
            if (DB::connection()->getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     * This is not reversible as data is lost during truncation
     */
    public function down(): void
    {
        // This migration cannot be reversed as it destroys data
        throw new \Exception('This migration cannot be reversed as it truncates data tables.');
    }
};
