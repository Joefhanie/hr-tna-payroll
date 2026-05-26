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
        if (!Schema::hasTable('tap_records') || Schema::hasColumn('tap_records', 'employee_id')) {
            return;
        }

        Schema::table('tap_records', function (Blueprint $table) {
            $table->unsignedInteger('employee_id')
                ->nullable()
                ->after('id');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tap_records') || !Schema::hasColumn('tap_records', 'employee_id')) {
            return;
        }

        Schema::table('tap_records', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
