<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('supervisor_assignments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the legacy table structure if an older database needs to roll back.
        Schema::create('supervisor_assignments', function ($table) {
            $table->id();
            $table->unsignedInteger('supervisor_id');
            $table->string('location', 120);
            $table->date('date');
            $table->timestamps();

            $table->unique(['supervisor_id', 'date'], 'uq_sv_date');
            $table->foreign('supervisor_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }
};
