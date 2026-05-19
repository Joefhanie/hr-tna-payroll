<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('government_contribution_rates')->updateOrInsert(
            ['name' => 'SSS'],
            [
                'employee_rate' => 0.0500,
                'employer_rate' => 0.1000,
                'description' => 'Approximate split based on the provided reference: employee 5%, employer 10%. Actual SSS uses the official MSC table.',
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('government_contribution_rates')->updateOrInsert(
            ['name' => 'PhilHealth'],
            [
                'employee_rate' => 0.0250,
                'employer_rate' => 0.0250,
                'description' => 'Shared equally at 5% total contribution under the provided reference. Actual rates remain subject to PhilHealth rules and salary caps.',
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('government_contribution_rates')->updateOrInsert(
            ['name' => 'Pag-IBIG'],
            [
                'employee_rate' => 0.0200,
                'employer_rate' => 0.0200,
                'description' => 'Standard simplified rate using the common 2% employee / 2% employer setup. Actual Pag-IBIG computation may vary by salary bracket and ceiling.',
                'is_active' => 1,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('government_contribution_rates')
            ->whereIn('name', ['SSS', 'PhilHealth', 'Pag-IBIG'])
            ->delete();
    }
};
