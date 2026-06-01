<?php

namespace Tests\Unit;

use App\Models\PayslipDispute;
use App\Models\PayslipLineItem;
use App\Services\PayrollService;
use Tests\TestCase;

class PayslipDisputeAdjustmentTest extends TestCase
{
    public function test_disputed_deduction_creates_credit_for_difference(): void
    {
        $service = app(PayrollService::class);

        $dispute = new PayslipDispute([
            'dispute_amount' => 1000,
            'dispute_reason' => 'Late deduction dispute',
        ]);
        $dispute->setRelation('lineItem', new PayslipLineItem([
            'component_type' => 2,
            'description' => 'Late Deduction',
            'amount' => 1500,
            'is_taxable' => false,
        ]));

        $adjustment = $service->buildDisputeAdjustment($dispute);

        $this->assertNotNull($adjustment);
        $this->assertSame(1, $adjustment['component_type']);
        $this->assertSame(500.0, $adjustment['amount']);
        $this->assertSame('Disputes: Late Deduction', $adjustment['description']);
        $this->assertFalse($adjustment['is_taxable']);
    }

    public function test_disputed_bonus_creates_deduction_for_difference(): void
    {
        $service = app(PayrollService::class);

        $dispute = new PayslipDispute([
            'dispute_amount' => 500,
            'dispute_reason' => 'Bonus dispute',
        ]);
        $dispute->setRelation('lineItem', new PayslipLineItem([
            'component_type' => 1,
            'description' => 'Bonus',
            'amount' => 1000,
            'is_taxable' => true,
        ]));

        $adjustment = $service->buildDisputeAdjustment($dispute);

        $this->assertNotNull($adjustment);
        $this->assertSame(2, $adjustment['component_type']);
        $this->assertSame(500.0, $adjustment['amount']);
        $this->assertSame('Disputes: Bonus', $adjustment['description']);
        $this->assertTrue($adjustment['is_taxable']);
    }
}