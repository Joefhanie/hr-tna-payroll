<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shift_assignments')) {
            return;
        }

        if (!Schema::hasColumn('shift_assignments', 'status')) {
            Schema::table('shift_assignments', function (Blueprint $table) {
                $table->unsignedSmallInteger('status')->default(1)->after('effective_to');
            });
        }

        DB::table('shift_assignments')
            ->whereNull('status')
            ->update(['status' => 1]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('shift_assignments') || !Schema::hasColumn('shift_assignments', 'status')) {
            return;
        }

        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
