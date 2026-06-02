<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tap_record_sync_queue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tap_record_id')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Create a DB trigger to push new tap_records into the queue
        // Note: MySQL-specific trigger SQL. Adjust if using another engine.
        DB::unprepared(<<<'SQL'
        CREATE TRIGGER trg_tap_records_after_insert_sync_queue
        AFTER INSERT ON tap_records
        FOR EACH ROW
        BEGIN
            INSERT INTO tap_record_sync_queue (tap_record_id, created_at, updated_at)
            VALUES (NEW.id, NOW(), NOW());
        END;
        SQL
        );
    }

    public function down(): void
    {
        // Drop trigger if exists
        DB::unprepared('DROP TRIGGER IF EXISTS trg_tap_records_after_insert_sync_queue');
        Schema::dropIfExists('tap_record_sync_queue');
    }
};
