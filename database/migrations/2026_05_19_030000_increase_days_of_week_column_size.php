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
        if (DB::getDriverName() === 'sqlite' || !Schema::hasTable('shifts') || !Schema::hasColumn('shifts', 'days_of_week')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            $table->string('days_of_week', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' || !Schema::hasTable('shifts') || !Schema::hasColumn('shifts', 'days_of_week')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            $table->string('days_of_week', 20)->change();
        });
    }
};
