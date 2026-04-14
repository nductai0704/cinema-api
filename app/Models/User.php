<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'username',
    'password_hash',
    'full_name',
    'email',
    'phone',
    'date_of_birth',
    'gender',
    'role_id',
    'cinema_id',
    'status',
])]
#[Hidden(['password_hash', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = true;

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAFF = 'staff';
    public const ROLE_CUSTOMER = 'customer';

    public static function roleNames(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_STAFF,
            self::ROLE_CUSTOMER,
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }

    public function holds()
    {
        return $this->hasMany(SeatHold::class, 'user_id', 'user_id');
    }

    public function news()
    {
        return $this->hasMany(News::class, 'created_by', 'user_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && strcasecmp($this->role->role_name, $roleName) === 0;
    }

    public function inRoles(array $roleNames): bool
    {
        return $this->role && in_array(strtolower($this->role->role_name), array_map('strtolower', $roleNames), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(self::ROLE_STAFF);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(self::ROLE_CUSTOMER);
    }

    public function isActive(): bool
    {
        return strtolower($this->status ?? '') === 'active';
    }

    public function isCinemaActive(): bool
    {
        $this->loadMissing('cinema');

        return $this->cinema && strtolower($this->cinema->status ?? '') === 'active';
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
