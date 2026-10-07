<?php

namespace VistiqCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['status' => 'active', 'session_version' => 0];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime', 'last_active_at' => 'datetime'];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $code): bool
    {
        return $this->roles()->where('code', $code)->exists();
    }

    public function hasPermission(string $code): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->hasRole('super-admin') || $this->roles()->whereHas('permissions', fn ($query) => $query->where('code', $code))->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('admin.access');
    }
}
