<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipDispute extends Model
{
    protected $fillable = [
        'employee_id',
        'payslip_id',
        'payslip_line_item_id',
        'dispute_amount',
        'dispute_reason',
        'status',
        'hr_notes',
        'resolved_by',
        'resolved_at',
        'adjustment_pay_run_id',
        'adjustment_payslip_id',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'dispute_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
    
    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(PayslipLineItem::class, 'payslip_line_item_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function adjustmentPayRun(): BelongsTo
    {
        return $this->belongsTo(PayRun::class, 'adjustment_pay_run_id');
    }

    public function adjustmentPayslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'adjustment_payslip_id');
    }
}
