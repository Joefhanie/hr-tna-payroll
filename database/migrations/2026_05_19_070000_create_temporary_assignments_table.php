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
        if (!Schema::hasTable('temporary_assignments')) {
            Schema::create('temporary_assignments', function (Blueprint $table) {
                // Use INT primary key to match existing databases that use INT for users.id
                $table->increments('id');
                // Match users.id (INT) — use signed INT unless your users.id is unsigned
                $table->integer('user_id')->index();
                $table->tinyInteger('temporary_role');
                $table->tinyInteger('original_role');
                // Use DATETIME to allow time precision
                $table->dateTime('from_date');
                $table->dateTime('to_date');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'is_active', 'to_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_assignments');
    }
};
