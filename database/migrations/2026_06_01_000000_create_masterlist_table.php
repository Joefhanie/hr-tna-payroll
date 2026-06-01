<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('masterlist')) {
            Schema::create('masterlist', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';

                $table->increments('id');
                $table->string('name', 250);
                $table->string('contact_number', 20);
                $table->string('email', 250);
                $table->string('emergency_contact', 50);
                $table->integer('company_id');
                $table->string('uid', 250);
                $table->integer('is_admin');
                $table->integer('status');
                $table->dateTime('created_at')->useCurrent();
                $table->integer('created_by');
                $table->dateTime('updated_at')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('deleted_at')->nullable();
                $table->integer('deleted_by')->nullable();
                $table->boolean('is_deleted')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('masterlist');
    }
};
