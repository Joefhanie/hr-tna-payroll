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
        Schema::table('pay_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('pay_runs', 'deduct_government_contributions')) {
                $table->boolean('deduct_government_contributions')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pay_runs', function (Blueprint $table) {
            if (Schema::hasColumn('pay_runs', 'deduct_government_contributions')) {
                $table->dropColumn('deduct_government_contributions');
            }
        });
    }
};
