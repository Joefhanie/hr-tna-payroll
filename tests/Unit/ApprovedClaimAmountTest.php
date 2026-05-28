<?php

namespace Tests\Unit;

use App\Models\PreviousClaim;
use App\Services\PayrollService;
use Carbon\Carbon;
use Tests\TestCase;

class ApprovedClaimAmountTest extends TestCase
{
    public function test_calculates_overtime_amount_from_approved_request_details(): void
    {
        $service = new PayrollService();

        $claim = new PreviousClaim([
            'claim_type' => 'Overtime',
            'amount' => 0,
            'start_time' => '08:00',
            'end_time' => '10:30',
        ]);

        $amount = $service->calculateApprovedRequestAmount(
            $claim,
            30000.00,
            8.0,
            1.25,
            0.10,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-15')
        );

        $this->assertSame(781.25, $amount);
    }

    public function test_uses_stored_amount_when_request_is_already_valued(): void
    {
        $service = new PayrollService();

        $claim = new PreviousClaim([
            'claim_type' => 'Night Differential',
            'amount' => 420.55,
        ]);

        $amount = $service->calculateApprovedRequestAmount(
            $claim,
            30000.00,
            8.0,
            1.25,
            0.10,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-15')
        );

        $this->assertSame(420.55, $amount);
    }
}
