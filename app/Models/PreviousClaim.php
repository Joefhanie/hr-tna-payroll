<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreviousClaim extends Model
{
    protected $fillable = [
        'employee_id',
        'claim_type',
        'claim_date',
        'amount',
        'description',
        'supporting_document',
        'status',
        'reviewed_by',
        'reviewed_at',
        'hr_notes',
        'pay_run_id',
        'submitted_by',
    ];

    protected $casts = [
        'claim_date'  => 'date',
        'reviewed_at' => 'datetime',
        'amount'      => 'decimal:2',
        'status'      => 'integer',
    ];

    // Status constants
    const STATUS_PENDING  = 1;
    const STATUS_APPROVED = 2;
    const STATUS_DECLINED = 3;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayRun::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DECLINED => 'Declined',
            default               => 'Pending',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'badge-green',
            self::STATUS_DECLINED => 'badge-red',
            default               => 'badge-yellow',
        };
    }

    public static function claimTypes(): array
    {
        return [
            'Overtime',
            'Allowance',
            'Reimbursement',
            'Bonus',
            'Night Differential',
            'Holiday Pay',
            'Other',
        ];
    }
}
