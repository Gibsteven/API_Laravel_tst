<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'is_banned',
        'rewards',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'rewards' => 'array',
        ];
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param array|string $roles
     * @return bool
     */
    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        return in_array($this->role, $roles);
    }
    
    /**
     * Check if the user is SuperAdmin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole('superadmin');
    }

    /**
     * Check if the user is Admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole('admin');
    }
    
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members');
    }
}