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
        if (!Schema::hasTable('tax_brackets')) {
            Schema::create('tax_brackets', function (Blueprint $table) {
                $table->id();
                $table->decimal('threshold', 14, 2);
                $table->decimal('rate', 5, 4);
                $table->string('label', 120)->nullable();
                $table->text('notes')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('government_contribution_rates')) {
            Schema::create('government_contribution_rates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->decimal('employee_rate', 5, 4);
                $table->decimal('employer_rate', 5, 4);
                $table->text('description')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('deduction_rules')) {
            Schema::create('deduction_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->enum('type', ['Fixed', 'Percentage', 'Prorated'])->default('Fixed');
                $table->decimal('amount', 10, 2)->nullable();
                $table->decimal('rate', 5, 4)->nullable();
                $table->string('scope', 120)->nullable();
                $table->text('description')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deduction_rules');
        Schema::dropIfExists('government_contribution_rates');
        Schema::dropIfExists('tax_brackets');
    }
};
