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
        Schema::table('salary_records', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_records', 'attendance_overtime_multiplier')) {
                $table->decimal('attendance_overtime_multiplier', 8, 4)->nullable()->after('daily_divisor');
            }

            if (!Schema::hasColumn('salary_records', 'attendance_night_differential_multiplier')) {
                $table->decimal('attendance_night_differential_multiplier', 8, 4)->nullable()->after('attendance_overtime_multiplier');
            }

            if (!Schema::hasColumn('salary_records', 'attendance_late_deduction_multiplier')) {
                $table->decimal('attendance_late_deduction_multiplier', 8, 4)->nullable()->after('attendance_night_differential_multiplier');
            }

            if (!Schema::hasColumn('salary_records', 'attendance_undertime_deduction_multiplier')) {
                $table->decimal('attendance_undertime_deduction_multiplier', 8, 4)->nullable()->after('attendance_late_deduction_multiplier');
            }

            if (!Schema::hasColumn('salary_records', 'attendance_absence_deduction_multiplier')) {
                $table->decimal('attendance_absence_deduction_multiplier', 8, 4)->nullable()->after('attendance_undertime_deduction_multiplier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            if (Schema::hasColumn('salary_records', 'attendance_absence_deduction_multiplier')) {
                $table->dropColumn('attendance_absence_deduction_multiplier');
            }

            if (Schema::hasColumn('salary_records', 'attendance_undertime_deduction_multiplier')) {
                $table->dropColumn('attendance_undertime_deduction_multiplier');
            }

            if (Schema::hasColumn('salary_records', 'attendance_late_deduction_multiplier')) {
                $table->dropColumn('attendance_late_deduction_multiplier');
            }

            if (Schema::hasColumn('salary_records', 'attendance_night_differential_multiplier')) {
                $table->dropColumn('attendance_night_differential_multiplier');
            }

            if (Schema::hasColumn('salary_records', 'attendance_overtime_multiplier')) {
                $table->dropColumn('attendance_overtime_multiplier');
            }
        });
    }
};