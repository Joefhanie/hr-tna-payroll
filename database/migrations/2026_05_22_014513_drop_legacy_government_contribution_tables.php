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
        Schema::dropIfExists('employee_government_contribution');
        Schema::dropIfExists('government_contribution_rates');

        // Clean up orphaned payslip line items (where payslip_id doesn't exist in payslips)
        DB::table('payslip_line_items')
            ->whereNotIn('payslip_id', function ($query) {
                $query->select('id')->from('payslips');
            })->delete();

        // Clean up legacy flat-rate SSS, PhilHealth, Pag-IBIG entries from payslip_line_items
        DB::table('payslip_line_items')
            ->whereIn('description', ['SSS', 'PhilHealth', 'Pag-IBIG'])
            ->delete();

        // Clean up orphaned government contributions (where payslip_id doesn't exist in payslips)
        DB::table('government_contributions')
            ->whereNotIn('payslip_id', function ($query) {
                $query->select('id')->from('payslips');
            })->delete();

        // Clean up legacy flat-rate SSS, PhilHealth, Pag-IBIG entries from government_contributions
        DB::table('government_contributions')
            ->whereIn('contribution_type', ['SSS', 'PhilHealth', 'Pag-IBIG'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('government_contribution_rates')) {
            Schema::create('government_contribution_rates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->decimal('employee_rate', 5, 4);
                $table->decimal('employer_rate', 5, 4);
                $table->text('description')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('employee_government_contribution')) {
            Schema::create('employee_government_contribution', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('government_contribution_rate_id');
                $table->timestamps();

                $table->unique(['employee_id', 'government_contribution_rate_id'], 'emp_gov_contrib_unique');
            });
        }
    }
};
