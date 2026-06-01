<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $table = 'machine';

    protected $fillable = [
        'description',
        'auth_code',
        'company_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public $timestamps = false;
}
