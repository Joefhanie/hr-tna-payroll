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
        if (!Schema::hasColumn('field_records', 'location')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->string('location', 120)->nullable()->after('remarks');
            });
        }

        if (!Schema::hasColumn('field_records', 'notes')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('location');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('field_records', 'notes')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }

        if (Schema::hasColumn('field_records', 'location')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }
};
