<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Model
{
    protected $table = 'employees';
    public $timestamps = true;

    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'birth_date',
        'gender',
        'nationality',
        'marital_status',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'country',
        'status',
        'employment_type',
        'hire_date',
        'regularization_date',
        'termination_date',
        'termination_reason',
        'position_id',
        'department_id',
        'manager_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'regularization_date' => 'date',
        'termination_date' => 'date',
        'status' => 'integer',
        'employment_type' => 'integer',
    ];

    /**
     * Get the department that the employee belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position of the employee.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the manager of the employee.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the subordinates of the employee.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get the emergency contacts for the employee.
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * Get the government IDs for the employee.
     */
    public function governmentIds(): HasMany
    {
        return $this->hasMany(GovernmentId::class);
    }

    /**
     * Get the salary records for the employee.
     */
    public function salaryRecords(): HasMany
    {
        return $this->hasMany(SalaryRecord::class);
    }

    /**
     * Get the documents for the employee.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        $name = "{$this->first_name} {$this->last_name}";
        if ($this->middle_name) {
            $name = "{$this->first_name} {$this->middle_name} {$this->last_name}";
        }
        return $name;
    }

    /**
     * Tax brackets assigned to this employee.
     */
    public function taxBrackets(): BelongsToMany
    {
        return $this->belongsToMany(TaxBracket::class, 'employee_tax_bracket')->withTimestamps();
    }

    /**
     * Government contributions assigned to this employee.
     */
    public function governmentContributionRates(): BelongsToMany
    {
        return $this->belongsToMany(GovernmentContributionRate::class, 'employee_government_contribution')->withTimestamps();
    }

    /**
     * Deduction rules assigned to this employee.
     */
    public function deductionRules(): BelongsToMany
    {
        return $this->belongsToMany(DeductionRule::class, 'employee_deduction_rule')->withTimestamps();
    }
}
