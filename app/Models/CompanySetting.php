<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'tagline',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'website',
        'tin',
        'industry',
        'logo_path',
        'logo_dark_path',
    ];

    /**
     * Get the singleton company settings row, creating it if it doesn't exist.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
