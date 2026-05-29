<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineSetting extends Model
{
    protected $table = 'machine_settings';

    protected $fillable = [
        'machine_id',
        'description',
        'value',
        'type',
    ];

    public $timestamps = false;

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }
}
