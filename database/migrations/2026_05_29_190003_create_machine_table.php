<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('machine')) {
            Schema::create('machine', function (Blueprint $table) {
                $table->id();
                $table->string('description', 250);
                $table->string('auth_code', 250);
                $table->unsignedInteger('company_id');
                $table->integer('status');
                $table->dateTime('created_at')->useCurrent();
                $table->integer('created_by');
                $table->dateTime('updated_at')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('deleted_at')->nullable();
                $table->integer('deleted_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('machine');
    }
};