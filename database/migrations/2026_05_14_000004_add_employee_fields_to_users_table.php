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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->unique()->nullable();
            }

            if (!Schema::hasColumn('users', 'hire_date')) {
                $table->date('hire_date')->nullable();
            }

            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable();
            }

            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable();
            }

            if (!Schema::hasColumn('users', 'status')) {
                // status codes: 1=active, 0=inactive, 2=onboarding
                $table->tinyInteger('status')->default(1);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_filter([
            'employee_id' => Schema::hasColumn('users', 'employee_id'),
            'hire_date' => Schema::hasColumn('users', 'hire_date'),
            'position' => Schema::hasColumn('users', 'position'),
            'department' => Schema::hasColumn('users', 'department'),
            'status' => Schema::hasColumn('users', 'status'),
        ]);

        if (!empty($columns)) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn(array_keys($columns));
            });
        }
    }
};
