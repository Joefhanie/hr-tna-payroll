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

#[Fillable(['name', 'username', 'email', 'password', 'employee_id', 'role', 'status'])]
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
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
