<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxBracket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'threshold',
        'rate',
        'label',
        'notes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'threshold' => 'decimal:2',
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public static function getActiveBrackets()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('threshold')
            ->get();
    }
}
