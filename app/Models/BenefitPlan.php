<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BenefitPlan extends Model
{
    protected $table = 'benefit_plans';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'benefit_type',
        'provider',
        'coverage_details',
        'employer_cost',
        'employee_cost',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'employer_cost' => 'decimal:2',
        'employee_cost' => 'decimal:2',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(BenefitEnrollment::class, 'plan_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'benefit_enrollments', 'plan_id', 'employee_id')
            ->withPivot(['id', 'enrollment_date', 'coverage_start', 'coverage_end', 'status']);
    }

    public function getIconAttribute(): string
    {
        $type = strtolower($this->benefit_type);
        $name = strtolower($this->name);
        
        if (str_contains($name, 'dental')) {
            return 'heart-plus';
        }
        if (str_contains($type, 'health') || str_contains($type, 'medical')) {
            return 'heartbeat';
        }
        if (str_contains($type, 'insurance') || str_contains($type, 'life')) {
            return 'shield';
        }
        if (str_contains($type, 'government') || str_contains($type, 'mandatory') || str_contains($name, 'sss') || str_contains($name, 'philhealth') || str_contains($name, 'pag-ibig')) {
            return 'building-bank';
        }
        if (str_contains($type, 'allowance') || str_contains($name, 'allowance') || str_contains($name, 'wellness')) {
            return 'sparkles';
        }
        return 'gift';
    }
}
