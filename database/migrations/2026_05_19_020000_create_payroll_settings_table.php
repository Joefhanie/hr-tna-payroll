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
        if (!Schema::hasTable('payroll_settings')) {
            Schema::create('payroll_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('attendance_overtime_multiplier', 8, 4)->default(1.25);
                $table->decimal('attendance_night_differential_multiplier', 8, 4)->default(0.10);
                $table->decimal('attendance_late_deduction_multiplier', 8, 4)->default(1.00);
                $table->decimal('attendance_undertime_deduction_multiplier', 8, 4)->default(1.00);
                $table->decimal('attendance_absence_deduction_multiplier', 8, 4)->default(1.00);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
