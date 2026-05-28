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
     */
    public function up(): void
    {
        if (! Schema::hasTable('employee_plottings')) {
            return;
        }

        Schema::table('employee_plottings', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_plottings', 'location')) {
                $table->string('location', 120)->nullable()->after('date');
            }
        });

        Schema::table('employee_plottings', function (Blueprint $table) {
            if ($this->foreignKeyExists('employee_plottings', 'employee_plottings_employee_id_foreign')) {
                $table->dropForeign('employee_plottings_employee_id_foreign');
            }

            $hasEmployeeId = Schema::hasColumn('employee_plottings', 'employee_id');
            $hasDate = Schema::hasColumn('employee_plottings', 'date');
            $hasLocation = Schema::hasColumn('employee_plottings', 'location');

            if ($hasEmployeeId && $hasDate && $this->indexExists('employee_plottings', 'uq_emp_date')) {
                $table->dropUnique('uq_emp_date');
            }

            if ($hasEmployeeId && $hasDate && $hasLocation && ! $this->indexExists('employee_plottings', 'uq_emp_date_location')) {
                $table->unique(['employee_id', 'date', 'location'], 'uq_emp_date_location');
            }

            if (
                Schema::hasTable('employees')
                && $hasEmployeeId
                && ! $this->foreignKeyExists('employee_plottings', 'employee_plottings_employee_id_foreign')
            ) {
                $table->foreign('employee_id', 'employee_plottings_employee_id_foreign')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('employee_plottings')) {
            return;
        }

        Schema::table('employee_plottings', function (Blueprint $table) {
            $hasEmployeeId = Schema::hasColumn('employee_plottings', 'employee_id');
            $hasDate = Schema::hasColumn('employee_plottings', 'date');

            if ($hasEmployeeId && $hasDate && $this->indexExists('employee_plottings', 'uq_emp_date_location')) {
                $table->dropUnique('uq_emp_date_location');
            }
            if ($hasEmployeeId && $hasDate && ! $this->indexExists('employee_plottings', 'uq_emp_date')) {
                $table->unique(['employee_id', 'date'], 'uq_emp_date');
            }
            if (Schema::hasColumn('employee_plottings', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};
