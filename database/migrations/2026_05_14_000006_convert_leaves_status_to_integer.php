<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leave_requests', 'status')) {
            $type = Schema::getColumnType('leave_requests', 'status');
            if (in_array(strtolower($type), ['integer', 'tinyint', 'int'])) {
                return;
            }
        }

        if (!Schema::hasColumn('leave_requests', 'status_new')) {
            DB::statement("ALTER TABLE `leave_requests` ADD COLUMN `status_new` TINYINT NOT NULL DEFAULT 1");

            DB::table('leave_requests')->where('status', 'pending')->update(['status_new' => 1]);
            DB::table('leave_requests')->where('status', 'approved')->update(['status_new' => 2]);
            DB::table('leave_requests')->where('status', 'rejected')->update(['status_new' => 3]);
        }

        if (Schema::hasColumn('leave_requests', 'status')) {
            DB::statement("ALTER TABLE `leave_requests` DROP COLUMN `status`");
        }

        DB::statement("ALTER TABLE `leave_requests` CHANGE `status_new` `status` TINYINT NOT NULL DEFAULT 1");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `leave_requests` ADD COLUMN `status_old` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::table('leave_requests')->where('status', 1)->update(['status_old' => 'pending']);
        DB::table('leave_requests')->where('status', 2)->update(['status_old' => 'approved']);
        DB::table('leave_requests')->where('status', 3)->update(['status_old' => 'rejected']);
        DB::statement("ALTER TABLE `leave_requests` DROP COLUMN `status`");
        DB::statement("ALTER TABLE `leave_requests` CHANGE `status_old` `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }
};
