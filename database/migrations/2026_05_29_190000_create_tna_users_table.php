<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tna_users')) {
            Schema::create('tna_users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 250);
                $table->unsignedInteger('company_id');
                $table->string('username', 250);
                $table->string('password', 250);
                $table->string('role', 250);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tna_users');
    }
};
