<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GovernmentPremium extends Model
{
    use SoftDeletes;

    protected $table = 'government_premiums';

    protected $fillable = [
        'name',
        'calculation_type',
        'employee_value',
        'employer_value',
        'basis',
        'description',
        'is_taxable',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'employee_value' => 'decimal:4',
        'employer_value' => 'decimal:4',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function active()
    {
        return self::where('is_active', true)
            ->with('brackets')
            ->orderBy('sort_order')
            ->get();
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(GovernmentPremiumBracket::class)->orderBy('sort_order');
    }
}
