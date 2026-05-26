<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingTaskTemplate extends Model
{
    protected $fillable = [
        'title',
        'category',
        'instructions',
        'assigned_role',
        'action_type',
        'document_type',
        'sequence',
        'is_active',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class, 'onboarding_task_template_id');
    }
}
