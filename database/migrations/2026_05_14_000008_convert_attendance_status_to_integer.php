<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `attendance` ADD COLUMN `status_new` TINYINT NOT NULL DEFAULT 1");

        DB::table('attendance')->where('status', 'present')->update(['status_new' => 1]);
        DB::table('attendance')->where('status', 'late')->update(['status_new' => 2]);
        DB::table('attendance')->where('status', 'absent')->update(['status_new' => 3]);
        DB::table('attendance')->where('status', 'excused')->update(['status_new' => 4]);

        DB::statement("ALTER TABLE `attendance` DROP COLUMN `status`");
        DB::statement("ALTER TABLE `attendance` CHANGE `status_new` `status` TINYINT NOT NULL DEFAULT 1");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `attendance` ADD COLUMN `status_old` VARCHAR(50) NOT NULL DEFAULT 'present'");
        DB::table('attendance')->where('status', 1)->update(['status_old' => 'present']);
        DB::table('attendance')->where('status', 2)->update(['status_old' => 'late']);
        DB::table('attendance')->where('status', 3)->update(['status_old' => 'absent']);
        DB::table('attendance')->where('status', 4)->update(['status_old' => 'excused']);
        DB::statement("ALTER TABLE `attendance` DROP COLUMN `status`");
        DB::statement("ALTER TABLE `attendance` CHANGE `status_old` `status` VARCHAR(50) NOT NULL DEFAULT 'present'");
    }
};
