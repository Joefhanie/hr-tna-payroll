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
        if (!Schema::hasTable('shifts')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'is_flexible')) {
                $table->boolean('is_flexible')->default(false)->after('is_night_shift');
            }
            if (!Schema::hasColumn('shifts', 'flexible_hours')) {
                $table->integer('flexible_hours')->nullable()->default(2)->after('is_flexible');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('shifts')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'flexible_hours')) {
                $table->dropColumn('flexible_hours');
            }
            if (Schema::hasColumn('shifts', 'is_flexible')) {
                $table->dropColumn('is_flexible');
            }
        });
    }
};
