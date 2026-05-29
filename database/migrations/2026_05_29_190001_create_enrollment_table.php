<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollment')) {
            Schema::create('enrollment', function (Blueprint $table) {
                $table->id();
                $table->dateTime('date');
                $table->string('uid', 250);
                $table->string('e_code', 250);
                $table->integer('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment');
    }
};