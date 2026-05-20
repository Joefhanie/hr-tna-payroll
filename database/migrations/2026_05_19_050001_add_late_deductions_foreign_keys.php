<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
        Schema::table('late_deductions', function (Blueprint $table) {
            $table->dropForeignKey(['employee_id']);
            $table->dropForeignKey(['time_log_id']);
            $table->dropForeignKey(['approved_by']);
        });
    }
};
