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
            $table->id();
            // Use unsignedBigInteger for user_id and index it instead of adding a foreign key
            // to avoid compatibility issues with existing users.id column types on some setups.
            $table->unsignedBigInteger('user_id')->index();
            $table->tinyInteger('temporary_role');
            $table->tinyInteger('original_role');
            $table->date('from_date');
            $table->date('to_date');
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
