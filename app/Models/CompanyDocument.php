<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocument extends Model
{
    public const CATEGORY_POLICIES = 'Policies';
    public const CATEGORY_EMPLOYEE_HANDBOOK = 'Employee Handbook';
    public const CATEGORY_COMPLIANCE = 'Compliance';
    public const CATEGORY_BENEFITS = 'Benefits';
    public const CATEGORY_CONTRACT = 'Contract';
    public const CATEGORY_OTHER = 'Other';

    protected $fillable = [
        'title',
        'category',
        'file_name',
        'file_path',
        'file_extension',
        'file_size',
        'file_size_kb',
        'description',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'file_size_kb' => 'decimal:2',
        'uploaded_at' => 'datetime',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_POLICIES,
            self::CATEGORY_EMPLOYEE_HANDBOOK,
            self::CATEGORY_COMPLIANCE,
            self::CATEGORY_BENEFITS,
            self::CATEGORY_CONTRACT,
            self::CATEGORY_OTHER,
        ];
    }
}
