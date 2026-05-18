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
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (! $database) {
            return;
        }

        $constraints = DB::select(
            'SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_TYPE = "FOREIGN KEY" AND TABLE_SCHEMA = ?',
            [$database]
        );

        foreach ($constraints as $c) {
            try {
                DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $c->TABLE_NAME, $c->CONSTRAINT_NAME));
            } catch (\Throwable $e) {
                // ignore failures, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible automatically. Leave as no-op.
    }
};
