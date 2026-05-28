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

    /**
     * Run the migrations.
     * Adds foreign keys and indexes for late_deductions table
     */
    public function up(): void
    {
        // Foreign keys are created in the 2026_05_19_050000_create_late_deductions_table migration
        // This migration is a placeholder for future alterations if needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('late_deductions')) {
            return;
        }

        Schema::table('late_deductions', function (Blueprint $table) {
            if ($this->foreignKeyExists('late_deductions', 'late_deductions_employee_id_foreign')) {
                $table->dropForeign('late_deductions_employee_id_foreign');
            }
            if ($this->foreignKeyExists('late_deductions', 'late_deductions_time_log_id_foreign')) {
                $table->dropForeign('late_deductions_time_log_id_foreign');
            }
            if ($this->foreignKeyExists('late_deductions', 'late_deductions_approved_by_foreign')) {
                $table->dropForeign('late_deductions_approved_by_foreign');
            }
        });
    }
};
