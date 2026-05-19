<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payrolls', 'status')) {
            $type = Schema::getColumnType('payrolls', 'status');
            if (in_array(strtolower($type), ['integer', 'tinyint', 'int'])) {
                return;
            }
        }

        if (!Schema::hasColumn('payrolls', 'status_new')) {
            DB::statement("ALTER TABLE `payrolls` ADD COLUMN `status_new` TINYINT NOT NULL DEFAULT 1");

            DB::table('payrolls')->where('status', 'processing')->update(['status_new' => 1]);
            DB::table('payrolls')->where('status', 'completed')->update(['status_new' => 2]);
            DB::table('payrolls')->where('status', 'failed')->update(['status_new' => 3]);
        }

        if (Schema::hasColumn('payrolls', 'status')) {
            DB::statement("ALTER TABLE `payrolls` DROP COLUMN `status`");
        }

        DB::statement("ALTER TABLE `payrolls` CHANGE `status_new` `status` TINYINT NOT NULL DEFAULT 1");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `payrolls` ADD COLUMN `status_old` VARCHAR(50) NOT NULL DEFAULT 'processing'");
        DB::table('payrolls')->where('status', 1)->update(['status_old' => 'processing']);
        DB::table('payrolls')->where('status', 2)->update(['status_old' => 'completed']);
        DB::table('payrolls')->where('status', 3)->update(['status_old' => 'failed']);
        DB::statement("ALTER TABLE `payrolls` DROP COLUMN `status`");
        DB::statement("ALTER TABLE `payrolls` CHANGE `status_old` `status` VARCHAR(50) NOT NULL DEFAULT 'processing'");
    }
};
