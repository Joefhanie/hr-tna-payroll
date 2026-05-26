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
        $taxBrackets = [
            ['threshold' => 0.00, 'rate' => 0.00, 'label' => 'Exempt', 'notes' => '₱20,833 and below', 'is_active' => 1, 'sort_order' => 0],
            ['threshold' => 20833.00, 'rate' => 0.15, 'label' => 'Bracket 2', 'notes' => 'Over ₱20,833 to ₱33,333', 'is_active' => 1, 'sort_order' => 1],
            ['threshold' => 33333.00, 'rate' => 0.20, 'label' => 'Bracket 3', 'notes' => 'Over ₱33,333 to ₱66,667', 'is_active' => 1, 'sort_order' => 2],
            ['threshold' => 66667.00, 'rate' => 0.25, 'label' => 'Bracket 4', 'notes' => 'Over ₱66,667 to ₱166,667', 'is_active' => 1, 'sort_order' => 3],
            ['threshold' => 166667.00, 'rate' => 0.30, 'label' => 'Bracket 5', 'notes' => 'Over ₱166,667 to ₱666,667', 'is_active' => 1, 'sort_order' => 4],
            ['threshold' => 666667.00, 'rate' => 0.35, 'label' => 'Bracket 6', 'notes' => 'Over ₱666,667', 'is_active' => 1, 'sort_order' => 5],
        ];

        foreach ($taxBrackets as $bracket) {
            DB::table('tax_brackets')->updateOrInsert(
                ['threshold' => $bracket['threshold']],
                [
                    'rate' => $bracket['rate'],
                    'label' => $bracket['label'],
                    'notes' => $bracket['notes'],
                    'is_active' => $bracket['is_active'],
                    'sort_order' => $bracket['sort_order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tax_brackets')
            ->whereIn('threshold', [0.00, 20833.00, 33333.00, 66667.00, 166667.00, 666667.00])
            ->delete();
    }
};
