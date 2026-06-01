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
        if (!Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('company_settings', 'use_machine')) {
                $table->boolean('use_machine')->default(false)->after('industry');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table) {
            if (Schema::hasColumn('company_settings', 'use_machine')) {
                $table->dropColumn('use_machine');
            }
        });
    }
};
