<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('function_settings')) {
            Schema::create('function_settings', function (Blueprint $table) {
                $table->id();
                $table->integer('function_id');
                $table->string('setting_value', 250)->nullable();
                $table->string('remarks', 250)->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->integer('updated_by')->nullable();
            });
        } elseif (! Schema::hasColumn('function_settings', 'remarks')) {
            Schema::table('function_settings', function (Blueprint $table) {
                $table->string('remarks', 250)->nullable()->after('setting_value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('function_settings') && Schema::hasColumn('function_settings', 'remarks')) {
            Schema::table('function_settings', function (Blueprint $table) {
                $table->dropColumn('remarks');
            });
            return;
        }

        Schema::dropIfExists('function_settings');
    }
};