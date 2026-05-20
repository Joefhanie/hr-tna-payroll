<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'doc_type',
        'file_name',
        'file_url',
        'file_size_kb',
        'issued_date',
        'expiry_date',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the employee associated with this document.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
