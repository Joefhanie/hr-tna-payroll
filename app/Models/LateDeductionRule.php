<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LateDeductionRule extends Model
{
    protected $table = 'late_deduction_rules';

    protected $fillable = [
        'name',
        'max_minutes',
        'deduction_hours',
        'sort_order',
    ];

    protected $casts = [
        'max_minutes' => 'integer',
        'deduction_hours' => 'decimal:2',
        'sort_order' => 'integer',
    ];
}
