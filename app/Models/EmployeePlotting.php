<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePlotting extends Model
{
    protected $table = 'employee_plottings';

    protected $fillable = [
        'empid',
        'sup_id',
        'date',
        'location',
        'amount',
        'payment_status',
        'posted',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'posted' => 'boolean',
    ];

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'empid', 'employee_code');
    }

    /**
     * Get the supervisor who scanned them in.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sup_id', 'employee_code');
    }
}
