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
        if (!Schema::hasTable('field_records') || Schema::hasColumn('field_records', 'notes_saved_at')) {
            return;
        }

        Schema::table('field_records', function (Blueprint $table) {
            $table->timestamp('notes_saved_at')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('field_records') || !Schema::hasColumn('field_records', 'notes_saved_at')) {
            return;
        }

        Schema::table('field_records', function (Blueprint $table) {
            $table->dropColumn('notes_saved_at');
        });
    }
};
