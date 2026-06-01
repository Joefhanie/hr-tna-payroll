<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('function_settings')) {
            Schema::create('function_settings', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_0900_ai_ci';

                $table->increments('id');
                $table->integer('setting_value')->nullable();
                $table->string('remarks', 250)->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->integer('updated_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('function_settings');
    }
};
