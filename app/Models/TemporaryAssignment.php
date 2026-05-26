<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

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
        'granted_by',
    ];

    protected $casts = [
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'is_active' => 'boolean',
        'granted_by' => 'int',
    ];

    /**
     * Relationship to the user who granted the temporary access.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

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
                     ->where('from_date', '<=', now())
                     ->where('to_date', '>=', now());
    }

    /**
     * Scope to get expired temporary assignments.
     */
    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
                     ->where('to_date', '<', now());
    }
}
