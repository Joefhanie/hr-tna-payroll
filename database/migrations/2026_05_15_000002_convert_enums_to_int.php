<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert pay_runs.status from ENUM to INT
        if (Schema::hasColumn('pay_runs', 'status')) {
            DB::statement("ALTER TABLE `pay_runs` MODIFY `status` VARCHAR(255)");
            DB::statement("UPDATE `pay_runs` SET `status` = CASE `status`
                WHEN 'Draft' THEN '1'
                WHEN 'Processing' THEN '2'
                WHEN 'Completed' THEN '3'
                WHEN 'Cancelled' THEN '4'
                ELSE '1' END");
            DB::statement("ALTER TABLE `pay_runs` MODIFY `status` INT DEFAULT 1");
        }

        // Convert pay_runs.frequency from ENUM to INT
        if (Schema::hasColumn('pay_runs', 'frequency')) {
            DB::statement("ALTER TABLE `pay_runs` MODIFY `frequency` VARCHAR(255)");
            DB::statement("UPDATE `pay_runs` SET `frequency` = CASE `frequency`
                WHEN 'Weekly' THEN '1'
                WHEN 'Bi-weekly' THEN '2'
                WHEN 'Semi-monthly' THEN '3'
                WHEN 'Monthly' THEN '4'
                ELSE '4' END");
            DB::statement("ALTER TABLE `pay_runs` MODIFY `frequency` INT DEFAULT 4");
        }

        // Convert payslips.status from ENUM to INT
        if (Schema::hasColumn('payslips', 'status')) {
            DB::statement("ALTER TABLE `payslips` MODIFY `status` VARCHAR(255)");
            DB::statement("UPDATE `payslips` SET `status` = CASE `status`
                WHEN 'Draft' THEN '1'
                WHEN 'Approved' THEN '2'
                WHEN 'Released' THEN '3'
                ELSE '1' END");
            DB::statement("ALTER TABLE `payslips` MODIFY `status` INT DEFAULT 1");
        }

        // Convert payslip_line_items.component_type from ENUM to INT
        if (Schema::hasColumn('payslip_line_items', 'component_type')) {
            DB::statement("ALTER TABLE `payslip_line_items` MODIFY `component_type` VARCHAR(255)");
            DB::statement("UPDATE `payslip_line_items` SET `component_type` = CASE `component_type`
                WHEN 'Earning' THEN '1'
                WHEN 'Deduction' THEN '2'
                WHEN 'Tax' THEN '3'
                WHEN 'Government' THEN '4'
                ELSE '1' END");
            DB::statement("ALTER TABLE `payslip_line_items` MODIFY `component_type` INT NOT NULL");
        }

        // Convert employees.status from ENUM to INT
        if (Schema::hasColumn('employees', 'employment_status')) {
            DB::statement("ALTER TABLE `employees` MODIFY `employment_status` VARCHAR(255)");
            DB::statement("UPDATE `employees` SET `employment_status` = CASE `employment_status`
                WHEN 'active' THEN '1'
                WHEN 'inactive' THEN '2'
                WHEN 'terminated' THEN '3'
                WHEN 'on_leave' THEN '4'
                ELSE '1' END");
            DB::statement("ALTER TABLE `employees` MODIFY `employment_status` INT DEFAULT 1");
        }

        // Convert employees.employment_type from ENUM to INT
        if (Schema::hasColumn('employees', 'employment_type')) {
            DB::statement("ALTER TABLE `employees` MODIFY `employment_type` VARCHAR(255)");
            DB::statement("UPDATE `employees` SET `employment_type` = CASE `employment_type`
                WHEN 'full_time' THEN '1'
                WHEN 'part_time' THEN '2'
                WHEN 'contract' THEN '3'
                WHEN 'temporary' THEN '4'
                ELSE '1' END");
            DB::statement("ALTER TABLE `employees` MODIFY `employment_type` INT DEFAULT 1");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to ENUM if needed (commented out as reversions to ENUM are complex)
        // You would need to handle data migration carefully
    }
};
