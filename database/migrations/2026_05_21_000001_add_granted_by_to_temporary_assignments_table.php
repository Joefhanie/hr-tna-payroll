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
        if (Schema::hasTable('temporary_assignments') && !Schema::hasColumn('temporary_assignments', 'granted_by')) {
            Schema::table('temporary_assignments', function (Blueprint $table) {
                $table->integer('granted_by')->nullable()->after('is_active')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('temporary_assignments') && Schema::hasColumn('temporary_assignments', 'granted_by')) {
            Schema::table('temporary_assignments', function (Blueprint $table) {
                $table->dropIndex(['granted_by']);
                $table->dropColumn('granted_by');
            });
        }
    }
};
