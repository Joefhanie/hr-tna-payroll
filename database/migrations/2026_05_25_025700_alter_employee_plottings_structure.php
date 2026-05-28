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
            if ($this->foreignKeyExists('employee_plottings', 'employee_plottings_employee_id_foreign')) {
                $table->dropForeign('employee_plottings_employee_id_foreign');
            }

            if ($this->indexExists('employee_plottings', 'uq_emp_date_location')) {
                $table->dropUnique('uq_emp_date_location');
            }

            if (Schema::hasColumn('employee_plottings', 'employee_id')) {
                $table->dropColumn('employee_id');
            }

            if (! Schema::hasColumn('employee_plottings', 'empid')) {
                $table->string('empid', 30)->after('id');
            }
            if (! Schema::hasColumn('employee_plottings', 'sup_id')) {
                $table->string('sup_id', 30)->nullable()->after('empid');
            }
            if (! Schema::hasColumn('employee_plottings', 'payment_status')) {
                $table->string('payment_status', 20)->default('unpaid')->after('amount');
            }
            if (! Schema::hasColumn('employee_plottings', 'posted')) {
                $table->boolean('posted')->default(false)->after('payment_status');
            }

            if (
                Schema::hasColumn('employee_plottings', 'empid')
                && Schema::hasColumn('employee_plottings', 'date')
                && Schema::hasColumn('employee_plottings', 'location')
                && ! $this->indexExists('employee_plottings', 'uq_empid_date_location')
            ) {
                $table->unique(['empid', 'date', 'location'], 'uq_empid_date_location');
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
            if ($this->indexExists('employee_plottings', 'uq_empid_date_location')) {
                $table->dropUnique('uq_empid_date_location');
            }

            foreach (['empid', 'sup_id', 'payment_status', 'posted'] as $column) {
                if (Schema::hasColumn('employee_plottings', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (! Schema::hasColumn('employee_plottings', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->after('id');
            }
            if (
                Schema::hasColumn('employee_plottings', 'employee_id')
                && Schema::hasColumn('employee_plottings', 'date')
                && Schema::hasColumn('employee_plottings', 'location')
                && ! $this->indexExists('employee_plottings', 'uq_emp_date_location')
            ) {
                $table->unique(['employee_id', 'date', 'location'], 'uq_emp_date_location');
            }
            if (
                Schema::hasTable('employees')
                && Schema::hasColumn('employee_plottings', 'employee_id')
                && ! $this->foreignKeyExists('employee_plottings', 'employee_plottings_employee_id_foreign')
            ) {
                $table->foreign('employee_id', 'employee_plottings_employee_id_foreign')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('cascade');
            }
        });
    }
};
