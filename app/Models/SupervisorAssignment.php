<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorAssignment extends Model
{
    protected $table = 'supervisor_assignments';

    protected $fillable = [
        'supervisor_id',
        'location',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the supervisor employee.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }
}
