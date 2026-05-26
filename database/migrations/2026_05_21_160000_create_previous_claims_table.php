<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('previous_claims', function (Blueprint $table) {
            $table->bigIncrements('id');
            // All referenced tables use int (signed) PKs in this DB
            $table->unsignedInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->string('claim_type');
            $table->date('claim_date');
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->string('supporting_document')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('hr_notes')->nullable();
            $table->unsignedInteger('pay_run_id')->nullable();
            $table->foreign('pay_run_id')->references('id')->on('pay_runs')->nullOnDelete();
            $table->unsignedInteger('submitted_by')->nullable();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previous_claims');
    }
};
