<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'period_start',
        'period_end',
        'pay_date',
        'frequency',
        'status',
        'created_by',
        'finalized_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'pay_date' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalGrossAttribute()
    {
        return $this->payslips()->sum('gross_pay');
    }

    public function getTotalNetAttribute()
    {
        return $this->payslips()->sum('net_pay');
    }

    public function getTotalDeductionsAttribute()
    {
        return $this->payslips()->sum('total_deductions');
    }

    public function getEmployeeCountAttribute()
    {
        return $this->payslips()->count();
    }
}
