<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'employee_id', 'role', 'status', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Model attribute defaults.
     *
     * @var array<string,mixed>
     */
    protected $attributes = [
        'role' => 4,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => 'int',
        'permissions' => 'array',
    ];

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // HR role (4) has all permissions by default
        if ($this->role === 4) {
            return true;
        }

        // If user has specific overrides defined in JSON, check those
        if (is_array($this->permissions)) {
            return in_array($permission, $this->permissions);
        }

        // Otherwise, fall back to role defaults:
        $defaults = [
            2 => [ // Supervisor
                'employees.view',
                'onboarding.view',
                'onboarding.edit',
                'timekeeping.view',
                'leaves.view',
                'leaves.create',
            ],
            3 => [ // OIC
                'onboarding.view',
                'timekeeping.view',
                'timekeeping.create',
                'timekeeping.edit',
                'timekeeping.delete',
                'leaves.view',
                'leaves.create',
                'leaves.edit',
                'leaves.delete',
                'self-service.view',
                'self-service.create',
                'self-service.edit',
                'self-service.delete',
            ],
            1 => [ // Employee
                'onboarding.view',
                'timekeeping.view',
                'leaves.view',
                'leaves.create',
            ],
        ];

        $rolePermissions = $defaults[$this->role] ?? [];
        return in_array($permission, $rolePermissions);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the temporary assignments for the user.
     */
    public function temporaryAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TemporaryAssignment::class);
    }

    /**
     * Get a consistently formatted display name using middle initial.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name));
        $parts = array_values(array_filter($parts));

        if (count($parts) <= 2) {
            return trim((string) $this->name);
        }

        $firstName = $parts[0];
        $lastName = $parts[count($parts) - 1];
        $middleInitial = strtoupper(substr($parts[1], 0, 1)) . '.';

        return trim(implode(' ', [$firstName, $middleInitial, $lastName]));
    }
}
