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
        if (!Schema::hasColumn('field_records', 'session_id')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->unsignedInteger('session_id')->nullable()->after('id');
                $table->index('session_id', 'idx_field_records_session_id');
            });
        }

        if (!Schema::hasColumn('field_records', 'work_status')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->string('work_status', 20)->nullable()->default(null)->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('field_records', 'work_status')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->dropColumn('work_status');
            });
        }

        if (Schema::hasColumn('field_records', 'session_id')) {
            Schema::table('field_records', function (Blueprint $table) {
                $table->dropIndex('idx_field_records_session_id');
                $table->dropColumn('session_id');
            });
        }
    }
};
