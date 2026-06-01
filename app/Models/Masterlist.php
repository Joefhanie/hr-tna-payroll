<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Masterlist extends Model
{
    protected $table = 'masterlist';

    protected $fillable = [
        'emp_id',
        'name',
        'contact_number',
        'email',
        'emergency_contact',
        'uid',
        'is_admin',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_deleted',
    ];

    protected $casts = [
        'emp_id' => 'integer',
        'is_admin' => 'integer',
        'status' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'is_deleted' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
