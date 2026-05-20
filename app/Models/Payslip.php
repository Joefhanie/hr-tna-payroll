<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_run_id',
        'employee_id',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'currency',
        'status',
        'released_at',
    ];

    protected $casts = [
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'released_at' => 'datetime',
    ];

    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(PayslipLineItem::class);
    }

    public function governmentContributions(): HasMany
    {
        return $this->hasMany(GovernmentContribution::class);
    }
}
