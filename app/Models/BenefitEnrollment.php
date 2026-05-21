<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitEnrollment extends Model
{
    protected $table = 'benefit_enrollments';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'plan_id',
        'enrollment_date',
        'coverage_start',
        'coverage_end',
        'status',
        'enrolled_by',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'coverage_start' => 'date',
        'coverage_end' => 'date',
        'status' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BenefitPlan::class, 'plan_id');
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }
}
