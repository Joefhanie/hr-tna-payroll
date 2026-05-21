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
        if (!Schema::hasTable('government_premium_brackets')) {
            Schema::create('government_premium_brackets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('government_premium_id')->constrained('government_premiums')->cascadeOnDelete();
                $table->string('label', 160)->nullable();
                $table->decimal('min_compensation', 12, 2)->default(0);
                $table->decimal('max_compensation', 12, 2)->nullable();
                $table->enum('calculation_type', ['Fixed', 'Percentage'])->default('Fixed');
                $table->decimal('employee_value', 10, 4)->default(0);
                $table->decimal('employer_value', 10, 4)->default(0);
                $table->decimal('employer_extra_value', 10, 4)->default(0);
                $table->enum('basis', ['Monthly Compensation', 'Gross Pay', 'Taxable Pay'])->default('Monthly Compensation');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();

        $sssId = DB::table('government_premiums')->where('name', 'SSS Premium')->value('id');
        if (!$sssId) {
            $sssId = DB::table('government_premiums')->insertGetId([
                'name' => 'SSS Premium',
                'calculation_type' => 'Fixed',
                'employee_value' => 0,
                'employer_value' => 0,
                'basis' => 'Gross Pay',
                'description' => 'Official SSS employed-member contribution table effective January 2025.',
                'is_taxable' => false,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('government_premiums')->where('id', $sssId)->update([
                'description' => 'Official SSS employed-member contribution table effective January 2025.',
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
        }

        $philHealthId = DB::table('government_premiums')->where('name', 'PhilHealth Premium')->value('id');
        if (!$philHealthId) {
            $philHealthId = DB::table('government_premiums')->insertGetId([
                'name' => 'PhilHealth Premium',
                'calculation_type' => 'Percentage',
                'employee_value' => 2.5,
                'employer_value' => 2.5,
                'basis' => 'Gross Pay',
                'description' => 'Official PhilHealth 5% premium with PHP 10,000 floor and PHP 100,000 ceiling.',
                'is_taxable' => false,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('government_premiums')->where('id', $philHealthId)->update([
                'employee_value' => 2.5,
                'employer_value' => 2.5,
                'description' => 'Official PhilHealth 5% premium with PHP 10,000 floor and PHP 100,000 ceiling.',
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
        }

        DB::table('government_premium_brackets')->whereIn('government_premium_id', [$sssId, $philHealthId])->delete();

        $sssRows = [];
        for ($msc = 5000, $index = 0; $msc <= 35000; $msc += 500, $index++) {
            if ($msc === 5000) {
                $min = 0;
                $max = 5249.99;
                $label = 'Below PHP 5,250';
            } elseif ($msc === 35000) {
                $min = 34750;
                $max = null;
                $label = 'PHP 34,750 and above';
            } else {
                $min = $msc - 250;
                $max = $msc + 249.99;
                $label = 'PHP ' . number_format($min, 2) . ' - PHP ' . number_format($max, 2);
            }

            $sssRows[] = [
                'government_premium_id' => $sssId,
                'label' => $label,
                'min_compensation' => $min,
                'max_compensation' => $max,
                'calculation_type' => 'Fixed',
                'employee_value' => round($msc * 0.05, 2),
                'employer_value' => round($msc * 0.10, 2),
                'employer_extra_value' => $msc <= 14500 ? 10 : 30,
                'basis' => 'Monthly Compensation',
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('government_premium_brackets')->insert($sssRows);

        DB::table('government_premium_brackets')->insert([
            [
                'government_premium_id' => $philHealthId,
                'label' => 'PHP 10,000 and below',
                'min_compensation' => 0,
                'max_compensation' => 10000,
                'calculation_type' => 'Fixed',
                'employee_value' => 250,
                'employer_value' => 250,
                'employer_extra_value' => 0,
                'basis' => 'Monthly Compensation',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'government_premium_id' => $philHealthId,
                'label' => 'PHP 10,000.01 - PHP 99,999.99',
                'min_compensation' => 10000.01,
                'max_compensation' => 99999.99,
                'calculation_type' => 'Percentage',
                'employee_value' => 2.5,
                'employer_value' => 2.5,
                'employer_extra_value' => 0,
                'basis' => 'Monthly Compensation',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'government_premium_id' => $philHealthId,
                'label' => 'PHP 100,000 and above',
                'min_compensation' => 100000,
                'max_compensation' => null,
                'calculation_type' => 'Fixed',
                'employee_value' => 2500,
                'employer_value' => 2500,
                'employer_extra_value' => 0,
                'basis' => 'Monthly Compensation',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('government_premium_brackets');
    }
};
