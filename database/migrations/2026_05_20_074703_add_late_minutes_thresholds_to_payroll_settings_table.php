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
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->integer('late_tier1_max_minutes')->default(15)->after('late_grace_period_minutes');
            $table->integer('late_tier2_max_minutes')->default(30)->after('late_tier1_max_minutes');
            $table->integer('late_tier3_max_minutes')->default(60)->after('late_tier2_max_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn([
                'late_tier1_max_minutes',
                'late_tier2_max_minutes',
                'late_tier3_max_minutes',
            ]);
        });
    }
};
