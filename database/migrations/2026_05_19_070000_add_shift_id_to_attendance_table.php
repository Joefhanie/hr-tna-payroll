<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds shift linkage to attendance records.
     */
    public function up(): void
    {
        if (!Schema::hasTable('attendance') || Schema::hasColumn('attendance', 'shift_id')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            $table->unsignedInteger('shift_id')->nullable()->after('user_id');
            $table->index('shift_id', 'idx_attendance_shift_id');
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('attendance') || !Schema::hasColumn('attendance', 'shift_id')) {
            return;
        }

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex('idx_attendance_shift_id');
            $table->dropColumn('shift_id');
        });
    }
};
