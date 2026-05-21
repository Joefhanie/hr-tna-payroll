<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    public const ASSIGNED_ROLE_EMPLOYEE = 'employee';
    public const ASSIGNED_ROLE_HR = 'hr';
    public const ASSIGNED_ROLE_SUPERVISOR = 'supervisor';

    public const ACTION_CHECKLIST = 'checklist';
    public const ACTION_DOCUMENT_UPLOAD = 'document_upload';
    public const ACTION_ACKNOWLEDGEMENT = 'acknowledgement';

    public const DOCUMENT_TYPE_EMPLOYMENT_CONTRACT = 'Employment Contract';

    protected $fillable = [
        'onboarding_assignment_id',
        'onboarding_task_template_id',
        'title',
        'category',
        'instructions',
        'assigned_role',
        'action_type',
        'document_type',
        'submission_notes',
        'submission_file_name',
        'submission_file_path',
        'submitted_at',
        'sequence',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(OnboardingAssignment::class, 'onboarding_assignment_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTaskTemplate::class, 'onboarding_task_template_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isEmployeeTask(): bool
    {
        return $this->assigned_role === self::ASSIGNED_ROLE_EMPLOYEE;
    }
}
