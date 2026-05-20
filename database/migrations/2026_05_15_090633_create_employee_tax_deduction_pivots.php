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
        // Drop if partially created from a previous failed run
        Schema::dropIfExists('employee_deduction_rule');
        Schema::dropIfExists('employee_government_contribution');
        Schema::dropIfExists('employee_tax_bracket');

        // Pivot: which tax brackets apply to each employee
        Schema::create('employee_tax_bracket', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('tax_bracket_id');
            $table->timestamps();

            $table->unique(['employee_id', 'tax_bracket_id']);
        });

        // Pivot: which government contributions apply to each employee
        Schema::create('employee_government_contribution', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('government_contribution_rate_id');
            $table->timestamps();

            $table->unique(['employee_id', 'government_contribution_rate_id'], 'emp_gov_contrib_unique');
        });

        // Pivot: which deduction rules apply to each employee
        Schema::create('employee_deduction_rule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('deduction_rule_id');
            $table->timestamps();

            $table->unique(['employee_id', 'deduction_rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deduction_rule');
        Schema::dropIfExists('employee_government_contribution');
        Schema::dropIfExists('employee_tax_bracket');
    }
};
