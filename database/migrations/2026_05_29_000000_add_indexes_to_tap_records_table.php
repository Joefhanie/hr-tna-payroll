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
        if (!Schema::hasTable('tap_records')) {
            return;
        }

        Schema::table('tap_records', function (Blueprint $table) {
            if (Schema::hasColumn('tap_records', 'time')) {
                $table->index('time', 'tap_records_time_index');
            }

            if (Schema::hasColumn('tap_records', 'employee_id')) {
                $table->index(['employee_id', 'time'], 'tap_records_employee_time_index');
            }

            if (Schema::hasColumn('tap_records', 'masterlist_id')) {
                $table->index(['masterlist_id', 'time'], 'tap_records_masterlist_time_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tap_records')) {
            return;
        }

        Schema::table('tap_records', function (Blueprint $table) {
            if (Schema::hasColumn('tap_records', 'masterlist_id')) {
                $table->dropIndex('tap_records_masterlist_time_index');
            }

            if (Schema::hasColumn('tap_records', 'employee_id')) {
                $table->dropIndex('tap_records_employee_time_index');
            }

            if (Schema::hasColumn('tap_records', 'time')) {
                $table->dropIndex('tap_records_time_index');
            }
        });
    }
};
