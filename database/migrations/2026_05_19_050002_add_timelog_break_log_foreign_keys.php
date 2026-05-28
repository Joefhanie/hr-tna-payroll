<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    /**
     * Run the migrations.
     * Adds foreign keys to time_logs and break_logs tables
     */
    public function up(): void
    {
        if (
            Schema::hasTable('time_logs')
            && Schema::hasTable('employees')
            && Schema::hasColumn('time_logs', 'employee_id')
            && ! $this->foreignKeyExists('time_logs', 'time_logs_employee_id_foreign')
        ) {
            Schema::table('time_logs', function (Blueprint $table) {
                $table->foreign('employee_id', 'time_logs_employee_id_foreign')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        if (
            Schema::hasTable('break_logs')
            && Schema::hasTable('time_logs')
            && Schema::hasColumn('break_logs', 'time_log_id')
            && ! $this->foreignKeyExists('break_logs', 'break_logs_time_log_id_foreign')
        ) {
            Schema::table('break_logs', function (Blueprint $table) {
                $table->foreign('time_log_id', 'break_logs_time_log_id_foreign')
                    ->references('id')
                    ->on('time_logs')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        // Add indexes to time_logs for better query performance
        if (Schema::hasTable('time_logs')) {
            Schema::table('time_logs', function (Blueprint $table) {
                if (! $this->indexExists('time_logs', 'idx_tl_emp_date')) {
                    $table->index(['employee_id', 'log_date'], 'idx_tl_emp_date');
                }
                if (! $this->indexExists('time_logs', 'idx_tl_emp_clockin')) {
                    $table->index(['employee_id', 'clock_in'], 'idx_tl_emp_clockin');
                }
            });
        }

        // Add indexes to break_logs
        if (Schema::hasTable('break_logs')) {
            Schema::table('break_logs', function (Blueprint $table) {
                if (! $this->indexExists('break_logs', 'idx_bl_timelog')) {
                    $table->index('time_log_id', 'idx_bl_timelog');
                }
                if (! $this->indexExists('break_logs', 'idx_bl_type')) {
                    $table->index('break_type', 'idx_bl_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('break_logs')) {
            Schema::table('break_logs', function (Blueprint $table) {
                if ($this->foreignKeyExists('break_logs', 'break_logs_time_log_id_foreign')) {
                    $table->dropForeign('break_logs_time_log_id_foreign');
                }
                if ($this->indexExists('break_logs', 'idx_bl_timelog')) {
                    $table->dropIndex('idx_bl_timelog');
                }
                if ($this->indexExists('break_logs', 'idx_bl_type')) {
                    $table->dropIndex('idx_bl_type');
                }
            });
        }

        if (Schema::hasTable('time_logs')) {
            Schema::table('time_logs', function (Blueprint $table) {
                if ($this->foreignKeyExists('time_logs', 'time_logs_employee_id_foreign')) {
                    $table->dropForeign('time_logs_employee_id_foreign');
                }
                if ($this->indexExists('time_logs', 'idx_tl_emp_date')) {
                    $table->dropIndex('idx_tl_emp_date');
                }
                if ($this->indexExists('time_logs', 'idx_tl_emp_clockin')) {
                    $table->dropIndex('idx_tl_emp_clockin');
                }
            });
        }
    }
};
