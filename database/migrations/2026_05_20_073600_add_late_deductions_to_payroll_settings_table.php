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
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->integer('late_grace_period_minutes')->default(10)->after('attendance_absence_deduction_multiplier');
            $table->decimal('late_11_15_deduction_hours', 8, 4)->default(0.5000)->after('late_grace_period_minutes');
            $table->decimal('late_16_30_deduction_hours', 8, 4)->default(1.0000)->after('late_11_15_deduction_hours');
            $table->decimal('late_31_60_deduction_hours', 8, 4)->default(4.0000)->after('late_16_30_deduction_hours');
            $table->decimal('late_61_plus_deduction_hours', 8, 4)->default(8.0000)->after('late_31_60_deduction_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn([
                'late_grace_period_minutes',
                'late_11_15_deduction_hours',
                'late_16_30_deduction_hours',
                'late_31_60_deduction_hours',
                'late_61_plus_deduction_hours',
            ]);
        });
    }
};
