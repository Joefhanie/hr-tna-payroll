<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('machine_settings')) {
            Schema::create('machine_settings', function (Blueprint $table) {
                $table->id();
                $table->integer('machine_id');
                $table->string('description', 250);
                $table->string('value', 250);
                $table->string('type', 250);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_settings');
    }
};