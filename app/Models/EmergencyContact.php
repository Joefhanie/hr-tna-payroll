<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    protected $table = 'emergency_contacts';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'full_name',
        'relationship',
        'phone',
        'alt_phone',
        'address',
    ];

    /**
     * Get the employee that owns the emergency contact.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
