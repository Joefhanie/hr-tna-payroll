<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds support for flexible and cross-midnight shifts
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Add shift order for sorting (e.g., 1st shift, 2nd shift, etc.)
            if (!Schema::hasColumn('shifts', 'shift_order')) {
                $table->integer('shift_order')->default(1)->after('name');
            }

            // Add flag to indicate if shift crosses midnight (e.g., 10pm-7am)
            if (!Schema::hasColumn('shifts', 'crosses_midnight')) {
                $table->boolean('crosses_midnight')->default(false)->after('end_time');
            }

            // Add shift duration in minutes for quick reference (excludes breaks)
            if (!Schema::hasColumn('shifts', 'shift_duration_minutes')) {
                $table->integer('shift_duration_minutes')->nullable()->after('crosses_midnight');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['shift_order', 'crosses_midnight', 'shift_duration_minutes']);
        });
    }
};
