<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance') || !Schema::hasColumn('attendance', 'time')) {
            return;
        }

        DB::statement('ALTER TABLE `attendance` MODIFY `time` TIME NULL');

        DB::statement('UPDATE `attendance` SET `time` = NULL WHERE `punch_type` = "in" AND `status` IN (3, 4, 5)');
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance') || !Schema::hasColumn('attendance', 'time')) {
            return;
        }

        DB::statement('UPDATE `attendance` SET `time` = "00:00:00" WHERE `time` IS NULL');
        DB::statement('ALTER TABLE `attendance` MODIFY `time` TIME NOT NULL');
    }
};
