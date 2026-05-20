<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates table for tracking late time-in deductions and policies
     */
    public function up(): void
    {
        Schema::create('late_deductions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->primary();
            $table->unsignedInteger('time_log_id')->nullable()->comment('Reference to time_logs table');
            $table->unsignedInteger('employee_id')->nullable()->comment('Reference to employees table');
            $table->date('attendance_date')->comment('Date of the late attendance');
            $table->time('expected_time')->comment('Expected clock-in time (shift start)');
            $table->time('actual_time')->comment('Actual clock-in time');
            $table->integer('late_minutes')->comment('Total minutes late');
            $table->enum('deduction_type', ['none', 'grace_period', 'one_hour', 'half_day', 'absent'])->default('none')->comment('Type of deduction applied');
            $table->decimal('deduction_hours', 5, 2)->default(0)->comment('Hours deducted from pay');
            $table->decimal('hourly_rate', 10, 2)->nullable()->comment('Hourly rate for calculation');
            $table->decimal('deduction_amount', 10, 2)->nullable()->comment('Amount deducted from salary');
            $table->string('policy_version')->default('1.0')->comment('Version of late policy applied');
            $table->boolean('is_excused')->default(false)->comment('Whether late was excused/waived');
            $table->text('excuse_reason')->nullable()->comment('Reason for excuse if applicable');
            $table->unsignedInteger('approved_by')->nullable()->comment('HR/Manager who approved/waived');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('attendance_date');
            $table->index('deduction_type');
            $table->unique(['time_log_id'], 'unique_time_log_deduction');

            // Foreign keys
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('time_log_id')
                ->references('id')
                ->on('time_logs')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_deductions');
    }
};
