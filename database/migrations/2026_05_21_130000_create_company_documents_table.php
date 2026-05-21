<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_documents')) {
            return;
        }

        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('category', 100);
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->string('file_extension', 20)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->decimal('file_size_kb', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'uploaded_at']);
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_documents');
    }
};
