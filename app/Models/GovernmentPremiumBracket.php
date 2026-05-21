<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentPremiumBracket extends Model
{
    protected $fillable = [
        'government_premium_id',
        'label',
        'min_compensation',
        'max_compensation',
        'calculation_type',
        'employee_value',
        'employer_value',
        'employer_extra_value',
        'basis',
        'sort_order',
    ];

    protected $casts = [
        'min_compensation' => 'decimal:2',
        'max_compensation' => 'decimal:2',
        'employee_value' => 'decimal:4',
        'employer_value' => 'decimal:4',
        'employer_extra_value' => 'decimal:4',
    ];

    public function premium(): BelongsTo
    {
        return $this->belongsTo(GovernmentPremium::class, 'government_premium_id');
    }
}
