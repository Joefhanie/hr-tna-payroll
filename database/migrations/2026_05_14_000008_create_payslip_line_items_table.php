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
        if (!Schema::hasTable('payslip_line_items')) {
            Schema::create('payslip_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->enum('component_type', ['Earning', 'Deduction', 'Tax', 'Government']);
            $table->string('description')->comment('e.g. Basic Pay, Overtime, SSS, PhilHealth, Pag-IBIG, Withholding Tax');
            $table->decimal('amount', 14, 2);
            $table->boolean('is_taxable')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_line_items');
    }
};

