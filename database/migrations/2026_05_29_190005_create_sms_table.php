<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms')) {
            Schema::create('sms', function (Blueprint $table) {
                $table->id();
                $table->string('contact_number', 20);
                $table->unsignedInteger('masterlist_id');
                $table->string('message', 500);
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('machine_id');
                $table->dateTime('created_at')->useCurrent();
                $table->unsignedInteger('created_by');
                $table->dateTime('updated_at')->nullable();
                $table->integer('updated_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms');
    }
};