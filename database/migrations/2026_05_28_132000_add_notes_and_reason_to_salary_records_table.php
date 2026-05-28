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
        Schema::table('salary_records', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_records', 'reason')) {
                $table->string('reason', 200)->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('salary_records', 'notes')) {
                $table->string('notes', 300)->nullable()->after('reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            if (Schema::hasColumn('salary_records', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('salary_records', 'reason')) {
                $table->dropColumn('reason');
            }
        });
    }
};
