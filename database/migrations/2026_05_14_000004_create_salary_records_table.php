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
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('pay_frequency', ['weekly', 'bi_weekly', 'monthly', 'annual']);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->string('reason')->nullable(); // e.g., 'Raise', 'Promotion', 'Adjustment'
            $table->text('notes')->nullable();
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

