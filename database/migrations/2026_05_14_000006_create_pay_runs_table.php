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
        if (!Schema::hasTable('pay_runs')) {
            Schema::create('pay_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('e.g. June 2026 - 1st Half');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->enum('frequency', ['Monthly', 'Semi-monthly', 'Weekly', 'Bi-weekly'])->default('Semi-monthly');
            $table->enum('status', ['Draft', 'Processing', 'Completed', 'Cancelled'])->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_runs');
    }
};

