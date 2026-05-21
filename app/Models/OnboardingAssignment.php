<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingAssignment extends Model
{
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_COMPLETED = 2;

    protected $fillable = [
        'employee_id',
        'assigned_by',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class)->orderBy('sequence');
    }
}
