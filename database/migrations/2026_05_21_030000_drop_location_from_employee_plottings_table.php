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
            if (Schema::hasColumn('employee_plottings', 'location')) {
                $table->dropColumn('location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_plottings', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_plottings', 'location')) {
                $table->string('location', 120)->nullable()->after('date');
            }
        });
    }
};