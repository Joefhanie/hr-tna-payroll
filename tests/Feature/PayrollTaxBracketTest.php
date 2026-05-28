<?php

namespace Tests\Feature;

use App\Services\PayrollService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PayrollTaxBracketTest extends TestCase
{
    public function test_tax_is_applied_and_bracket_is_resolved_from_taxable_income_for_fixed_salary_employees(): void
    {
        $brackets = new Collection([
            (object) [
                'threshold' => 0.00,
                'rate' => 0.00,
                'label' => 'Exempt',
            ],
            (object) [
                'threshold' => 20833.00,
                'rate' => 0.15,
                'label' => 'Bracket 2',
            ],
            (object) [
                'threshold' => 33333.00,
                'rate' => 0.20,
                'label' => 'Bracket 3',
            ],
        ]);

        $service = app(PayrollService::class);

        $breakdown = $service->calculateTaxBreakdownFromBrackets($brackets, 30000.00);

        $this->assertSame('Bracket 2', $breakdown['bracket']?->label);
        $this->assertEquals(1375.05, $breakdown['tax']);
    }
}
