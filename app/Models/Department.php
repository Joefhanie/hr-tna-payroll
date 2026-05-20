<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    protected $table = 'departments';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'parent_dept_id',
    ];

    /**
     * Get the parent department.
     */
    public function parentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_dept_id');
    }

    /**
     * Get the child departments.
     */
    public function childDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_dept_id');
    }

    /**
     * Get all employees in this department.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all positions in this department.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
