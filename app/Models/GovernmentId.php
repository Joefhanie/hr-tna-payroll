<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentId extends Model
{
    protected $table = 'government_ids';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'id_type',
        'id_number',
        'issued_date',
        'expiry_date',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the employee that owns this government ID.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
