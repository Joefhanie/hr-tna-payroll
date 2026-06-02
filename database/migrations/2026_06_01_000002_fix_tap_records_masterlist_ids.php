<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tap_records') || !Schema::hasTable('employees') || !Schema::hasTable('masterlist')) {
            return;
        }

        DB::statement(<<<'SQL'
            UPDATE tap_records t
            INNER JOIN employees e ON e.id = t.employee_id
            INNER JOIN masterlist m ON m.emp_id = e.id
            SET t.masterlist_id = m.id
            WHERE t.masterlist_id IS NULL
               OR t.masterlist_id <> m.id
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tap_records') || !Schema::hasTable('employees')) {
            return;
        }

        DB::statement(<<<'SQL'
            UPDATE tap_records t
            INNER JOIN employees e ON e.id = t.employee_id
            SET t.masterlist_id = e.id
        SQL);
    }
};
