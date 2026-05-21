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
            if (!Schema::hasColumn('employee_plottings', 'location')) {
                $table->string('location', 120)->nullable()->after('date');
            }
        });

        Schema::table('employee_plottings', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('uq_emp_date');
            
            // Create new unique constraint including location
            $table->unique(['employee_id', 'date', 'location'], 'uq_emp_date_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_plottings', function (Blueprint $table) {
            $table->dropUnique('uq_emp_date_location');
            $table->unique(['employee_id', 'date'], 'uq_emp_date');
            $table->dropColumn('location');
        });
    }
};
