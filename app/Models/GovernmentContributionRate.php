<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GovernmentContributionRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'employee_rate',
        'employer_rate',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'employee_rate' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public static function getActiveRates()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
