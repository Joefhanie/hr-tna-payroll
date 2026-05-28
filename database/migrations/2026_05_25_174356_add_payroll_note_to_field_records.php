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
        if (!Schema::hasTable('field_records') || Schema::hasColumn('field_records', 'payroll_note')) {
            return;
        }

        Schema::table('field_records', function (Blueprint $table) {
            $table->text('payroll_note')->nullable()->after('notes_saved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('field_records') || !Schema::hasColumn('field_records', 'payroll_note')) {
            return;
        }

        Schema::table('field_records', function (Blueprint $table) {
            $table->dropColumn('payroll_note');
        });
    }
};
