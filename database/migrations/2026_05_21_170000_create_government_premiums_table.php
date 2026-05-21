<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('government_premiums')) {
            Schema::create('government_premiums', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->enum('calculation_type', ['Fixed', 'Percentage'])->default('Fixed');
                $table->decimal('employee_value', 10, 4)->default(0);
                $table->decimal('employer_value', 10, 4)->default(0);
                $table->enum('basis', ['Gross Pay', 'Taxable Pay'])->default('Gross Pay');
                $table->text('description')->nullable();
                $table->boolean('is_taxable')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });

            DB::table('government_premiums')->insert([
                [
                    'name' => 'SSS Premium',
                    'calculation_type' => 'Percentage',
                    'employee_value' => 0,
                    'employer_value' => 0,
                    'basis' => 'Gross Pay',
                    'description' => 'Template for employee and employer SSS premium share.',
                    'is_taxable' => false,
                    'is_active' => false,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'PhilHealth Premium',
                    'calculation_type' => 'Percentage',
                    'employee_value' => 0,
                    'employer_value' => 0,
                    'basis' => 'Gross Pay',
                    'description' => 'Template for employee and employer PhilHealth premium share.',
                    'is_taxable' => false,
                    'is_active' => false,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Pag-IBIG Premium',
                    'calculation_type' => 'Fixed',
                    'employee_value' => 0,
                    'employer_value' => 0,
                    'basis' => 'Gross Pay',
                    'description' => 'Template for fixed employee and employer Pag-IBIG premium share.',
                    'is_taxable' => false,
                    'is_active' => false,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('government_premiums');
    }
};
