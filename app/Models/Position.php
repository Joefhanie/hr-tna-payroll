<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    protected $table = 'positions';
    public $timestamps = true;

    protected $fillable = [
        'title',
        'level',
        'department_id',
        'min_salary',
        'max_salary',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
    ];

    /**
     * Get the department for this position.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all employees in this position.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
