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
     * Adds foreign keys to shift_assignments table
     */
    public function up(): void
    {
        if (! Schema::hasTable('shift_assignments')) {
            return;
        }

        if (
            Schema::hasTable('employees')
            && Schema::hasColumn('shift_assignments', 'employee_id')
            && ! $this->foreignKeyExists('shift_assignments', 'shift_assignments_employee_id_foreign')
        ) {
            Schema::table('shift_assignments', function (Blueprint $table) {
                $table->foreign('employee_id', 'shift_assignments_employee_id_foreign')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        if (
            Schema::hasTable('shifts')
            && Schema::hasColumn('shift_assignments', 'shift_id')
            && ! $this->foreignKeyExists('shift_assignments', 'shift_assignments_shift_id_foreign')
        ) {
            Schema::table('shift_assignments', function (Blueprint $table) {
                $table->foreign('shift_id', 'shift_assignments_shift_id_foreign')
                    ->references('id')
                    ->on('shifts')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        Schema::table('shift_assignments', function (Blueprint $table) {
            if (! $this->indexExists('shift_assignments', 'idx_sa_emp_effective')) {
                $table->index(['employee_id', 'effective_from'], 'idx_sa_emp_effective');
            }
            if (! $this->indexExists('shift_assignments', 'idx_sa_shift')) {
                $table->index('shift_id', 'idx_sa_shift');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('shift_assignments')) {
            return;
        }

        Schema::table('shift_assignments', function (Blueprint $table) {
            if ($this->foreignKeyExists('shift_assignments', 'shift_assignments_employee_id_foreign')) {
                $table->dropForeign('shift_assignments_employee_id_foreign');
            }
            if ($this->foreignKeyExists('shift_assignments', 'shift_assignments_shift_id_foreign')) {
                $table->dropForeign('shift_assignments_shift_id_foreign');
            }
            if ($this->indexExists('shift_assignments', 'idx_sa_emp_effective')) {
                $table->dropIndex('idx_sa_emp_effective');
            }
            if ($this->indexExists('shift_assignments', 'idx_sa_shift')) {
                $table->dropIndex('idx_sa_shift');
            }
        });
    }
};
