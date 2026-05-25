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
        Schema::table('employee_plottings', function (Blueprint $table) {
            // Drop foreign key and unique index that depend on employee_id
            $table->dropForeign('employee_plottings_employee_id_foreign');
            $table->dropUnique('uq_emp_date_location');
            
            // Drop old employee_id column
            $table->dropColumn('employee_id');
            
            // Add new columns
            $table->string('empid', 30)->after('id');
            $table->string('sup_id', 30)->nullable()->after('empid');
            $table->string('payment_status', 20)->default('unpaid')->after('amount');
            $table->boolean('posted')->default(false)->after('payment_status');
            
            // Add new unique index based on empid instead of employee_id
            $table->unique(['empid', 'date', 'location'], 'uq_empid_date_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_plottings', function (Blueprint $table) {
            $table->dropUnique('uq_empid_date_location');
            
            $table->dropColumn(['empid', 'sup_id', 'payment_status', 'posted']);
            
            $table->unsignedBigInteger('employee_id')->after('id');
            $table->unique(['employee_id', 'date', 'location'], 'uq_emp_date_location');
            $table->foreign('employee_id', 'employee_plottings_employee_id_foreign')->references('id')->on('employees')->onDelete('cascade');
        });
    }
};
