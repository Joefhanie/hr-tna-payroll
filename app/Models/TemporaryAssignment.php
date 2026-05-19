<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryAssignment extends Model
{
    protected $table = 'temporary_assignments';

    protected $fillable = [
        'user_id',
        'temporary_role',
        'original_role',
        'from_date',
        'to_date',
        'is_active',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user associated with this temporary assignment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active temporary assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('from_date', '<=', now()->toDateString())
                     ->where('to_date', '>=', now()->toDateString());
    }

    /**
     * Scope to get expired temporary assignments.
     */
    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
                     ->where('to_date', '<', now()->toDateString());
    }
}
