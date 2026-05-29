<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Masterlist extends Model
{
    protected $table = 'masterlist';

    protected $fillable = [
        'name',
        'contact_number',
        'email',
        'emergency_contact',
        'company_id',
        'uid',
        'is_admin',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_deleted',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'is_admin' => 'integer',
        'status' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'is_deleted' => 'boolean',
    ];

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'masterlist_id');
    }
}
