<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds foreign keys to shift_assignments table
     */
    public function up(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            try {
                $table->foreign('employee_id')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }

            try {
                $table->foreign('shift_id')
                    ->references('id')
                    ->on('shifts')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }

            // Add indexes for better query performance
            if (!Schema::hasIndex('shift_assignments', 'idx_sa_emp_effective')) {
                $table->index(['employee_id', 'effective_from'], 'idx_sa_emp_effective');
            }
            if (!Schema::hasIndex('shift_assignments', 'idx_sa_shift')) {
                $table->index('shift_id', 'idx_sa_shift');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropForeignKey(['employee_id']);
            $table->dropForeignKey(['shift_id']);
            $table->dropIndex('idx_sa_emp_effective');
            $table->dropIndex('idx_sa_shift');
        });
    }
};
