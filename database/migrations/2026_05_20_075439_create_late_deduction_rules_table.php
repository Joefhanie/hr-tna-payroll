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
        if (!Schema::hasTable('late_deduction_rules')) {
            Schema::create('late_deduction_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('max_minutes');
                $table->decimal('deduction_hours', 8, 2)->default(0.00);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('late_deduction_rules') && DB::table('late_deduction_rules')->count() === 0) {
            DB::table('late_deduction_rules')->insert([
                ['name' => 'Grace Period', 'max_minutes' => 10, 'deduction_hours' => 0.00, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Tier 1', 'max_minutes' => 15, 'deduction_hours' => 0.50, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Tier 2', 'max_minutes' => 30, 'deduction_hours' => 1.00, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Tier 3', 'max_minutes' => 60, 'deduction_hours' => 4.00, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Tier 4', 'max_minutes' => 99999, 'deduction_hours' => 8.00, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_deduction_rules');
    }
};
