<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payslip_disputes', function (Blueprint $table) {
            if (!Schema::hasColumn('payslip_disputes', 'adjustment_pay_run_id')) {
                $table->unsignedInteger('adjustment_pay_run_id')->nullable()->after('resolved_at');
                $table->foreign('adjustment_pay_run_id')
                    ->references('id')
                    ->on('pay_runs')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('payslip_disputes', 'adjustment_payslip_id')) {
                $table->unsignedInteger('adjustment_payslip_id')->nullable()->after('adjustment_pay_run_id');
                $table->foreign('adjustment_payslip_id')
                    ->references('id')
                    ->on('payslips')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslip_disputes', function (Blueprint $table) {
            if (Schema::hasColumn('payslip_disputes', 'adjustment_payslip_id')) {
                $table->dropForeign(['adjustment_payslip_id']);
                $table->dropColumn('adjustment_payslip_id');
            }

            if (Schema::hasColumn('payslip_disputes', 'adjustment_pay_run_id')) {
                $table->dropForeign(['adjustment_pay_run_id']);
                $table->dropColumn('adjustment_pay_run_id');
            }
        });
    }
};
