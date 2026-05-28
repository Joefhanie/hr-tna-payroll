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
        if (Schema::hasTable('payslip_disputes')) {
            return;
        }

        Schema::create('payslip_disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('payslip_id');
            $table->unsignedInteger('payslip_line_item_id')->nullable();
            $table->decimal('dispute_amount', 12, 2)->default(0);
            $table->text('dispute_reason');
            $table->tinyInteger('status')->default(1)->comment('1=Pending, 2=Resolved, 3=Rejected');
            $table->text('hr_notes')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Foreign keys using standard naming, since existing PKs are unsigned int
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('payslip_id')->references('id')->on('payslips')->cascadeOnDelete();
            $table->foreign('payslip_line_item_id')->references('id')->on('payslip_line_items')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_disputes');
    }
};
