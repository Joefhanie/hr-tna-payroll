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
        // 1. Create Departments Table if not exists
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->unsignedInteger('parent_dept_id')->nullable();
                $table->timestamps();

                $table->foreign('parent_dept_id')->references('id')->on('departments')->onDelete('set null');
            });
        }

        // 2. Create Positions Table if not exists
        if (!Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->string('level');
                $table->unsignedInteger('department_id');
                $table->decimal('min_salary', 10, 2);
                $table->decimal('max_salary', 10, 2);
                $table->timestamps();

                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            });
        }

        // 3. Fix Employee Table Columns (Self-Healing)
        Schema::table('employees', function (Blueprint $table) {
            // Rename old columns to match Employee model if needed
            if (Schema::hasColumn('employees', 'date_of_birth') && !Schema::hasColumn('employees', 'birth_date')) {
                $table->renameColumn('date_of_birth', 'birth_date');
            }
            if (Schema::hasColumn('employees', 'address') && !Schema::hasColumn('employees', 'address_line1')) {
                $table->renameColumn('address', 'address_line1');
            }
            if (Schema::hasColumn('employees', 'state') && !Schema::hasColumn('employees', 'province')) {
                $table->renameColumn('state', 'province');
            }
            if (Schema::hasColumn('employees', 'zip_code') && !Schema::hasColumn('employees', 'postal_code')) {
                $table->renameColumn('zip_code', 'postal_code');
            }
            if (Schema::hasColumn('employees', 'employee_id') && !Schema::hasColumn('employees', 'employee_code')) {
                $table->renameColumn('employee_id', 'employee_code');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('employees', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('employees', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('employees', 'nationality')) {
                $table->string('nationality')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('employees', 'marital_status')) {
                $table->string('marital_status')->nullable()->after('nationality');
            }
            if (!Schema::hasColumn('employees', 'department_id')) {
                $table->unsignedInteger('department_id')->nullable();
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            if (!Schema::hasColumn('employees', 'position_id')) {
                $table->unsignedInteger('position_id')->nullable();
                $table->foreign('position_id')->references('id')->on('positions')->onDelete('set null');
            }
            if (!Schema::hasColumn('employees', 'manager_id')) {
                $table->unsignedInteger('manager_id')->nullable();
                $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'manager_id')) {
                $table->dropForeign(['manager_id']);
                $table->dropColumn('manager_id');
            }
            if (Schema::hasColumn('employees', 'position_id')) {
                $table->dropForeign(['position_id']);
                $table->dropColumn('position_id');
            }
            if (Schema::hasColumn('employees', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('employees', 'marital_status')) {
                $table->dropColumn('marital_status');
            }
            if (Schema::hasColumn('employees', 'nationality')) {
                $table->dropColumn('nationality');
            }
            if (Schema::hasColumn('employees', 'address_line2')) {
                $table->dropColumn('address_line2');
            }
            if (Schema::hasColumn('employees', 'middle_name')) {
                $table->dropColumn('middle_name');
            }
            
            // Rename back to original names if they were renamed
            if (Schema::hasColumn('employees', 'birth_date') && !Schema::hasColumn('employees', 'date_of_birth')) {
                $table->renameColumn('birth_date', 'date_of_birth');
            }
            if (Schema::hasColumn('employees', 'address_line1') && !Schema::hasColumn('employees', 'address')) {
                $table->renameColumn('address_line1', 'address');
            }
            if (Schema::hasColumn('employees', 'province') && !Schema::hasColumn('employees', 'state')) {
                $table->renameColumn('province', 'state');
            }
            if (Schema::hasColumn('employees', 'postal_code') && !Schema::hasColumn('employees', 'zip_code')) {
                $table->renameColumn('postal_code', 'zip_code');
            }
            if (Schema::hasColumn('employees', 'employee_code') && !Schema::hasColumn('employees', 'employee_id')) {
                $table->renameColumn('employee_code', 'employee_id');
            }
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
