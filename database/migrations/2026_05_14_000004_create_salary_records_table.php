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
        if (!Schema::hasTable('salary_records')) {
            Schema::create('salary_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->decimal('amount', 14, 2);
                $table->char('currency', 3)->default('PHP');
                $table->integer('pay_frequency')->default(4); // 1=Hourly, 2=Daily, 3=Semi-monthly, 4=Monthly
                $table->date('effective_date');
                $table->date('end_date')->nullable();
                $table->string('reason', 200)->nullable();
                $table->string('notes', 300)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_records');
    }
};
