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
            if (!Schema::hasColumn('company_settings', 'brand_primary_color')) {
                $table->string('brand_primary_color', 7)->nullable()->after('logo_dark_path');
            }
            if (!Schema::hasColumn('company_settings', 'brand_secondary_color')) {
                $table->string('brand_secondary_color', 7)->nullable()->after('brand_primary_color');
            }
            if (!Schema::hasColumn('company_settings', 'brand_accent_color')) {
                $table->string('brand_accent_color', 7)->nullable()->after('brand_secondary_color');
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
            if (Schema::hasColumn('company_settings', 'brand_accent_color')) {
                $table->dropColumn('brand_accent_color');
            }
            if (Schema::hasColumn('company_settings', 'brand_secondary_color')) {
                $table->dropColumn('brand_secondary_color');
            }
            if (Schema::hasColumn('company_settings', 'brand_primary_color')) {
                $table->dropColumn('brand_primary_color');
            }
        });
    }
};
