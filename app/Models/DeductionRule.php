<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeductionRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'amount',
        'rate',
        'scope',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public static function getActiveRules()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
