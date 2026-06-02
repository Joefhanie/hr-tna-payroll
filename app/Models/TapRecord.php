<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TapRecord extends Model
{
    protected $table = 'tap_records';

    protected $fillable = [
        'employee_id',
        'masterlist_id',
        'machine_id',
        'time',
        'function',
        'status',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::created(function (self $tap) {
            try {
                app(\App\Services\TapRecordAttendanceService::class)->syncForTapRecord($tap);
            } catch (\Throwable $e) {
                // swallow errors; queue/trigger processing will still cover it
            }
        });
    }
}
