<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('attendance') && Schema::hasColumn('attendance', 'punch_type')) {
            DB::statement("ALTER TABLE `attendance` MODIFY `punch_type` VARCHAR(10) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendance') && Schema::hasColumn('attendance', 'punch_type')) {
            DB::statement("ALTER TABLE `attendance` MODIFY `punch_type` ENUM('in', 'out') NOT NULL");
        }
    }
};
