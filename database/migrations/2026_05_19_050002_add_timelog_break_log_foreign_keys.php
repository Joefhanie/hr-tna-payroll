<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds foreign keys to time_logs and break_logs tables
     */
    public function up(): void
    {
        // Add foreign keys to time_logs if they don't exist
        Schema::table('time_logs', function (Blueprint $table) {
            try {
                $table->foreign('employee_id')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
        });

        // Add foreign keys to break_logs if they don't exist
        Schema::table('break_logs', function (Blueprint $table) {
            try {
                $table->foreign('time_log_id')
                    ->references('id')
                    ->on('time_logs')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
        });

        // Add indexes to time_logs for better query performance
        Schema::table('time_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('time_logs', 'idx_tl_emp_date')) {
                $table->index(['employee_id', 'log_date'], 'idx_tl_emp_date');
            }
            if (!Schema::hasIndex('time_logs', 'idx_tl_emp_clockin')) {
                $table->index(['employee_id', 'clock_in'], 'idx_tl_emp_clockin');
            }
        });

        // Add indexes to break_logs
        Schema::table('break_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('break_logs', 'idx_bl_timelog')) {
                $table->index('time_log_id', 'idx_bl_timelog');
            }
            if (!Schema::hasIndex('break_logs', 'idx_bl_type')) {
                $table->index('break_type', 'idx_bl_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('break_logs', function (Blueprint $table) {
            $table->dropForeignKey(['time_log_id']);
            $table->dropIndex('idx_bl_timelog');
            $table->dropIndex('idx_bl_type');
        });

        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropForeignKey(['employee_id']);
            $table->dropIndex('idx_tl_emp_date');
            $table->dropIndex('idx_tl_emp_clockin');
        });
    }
};
