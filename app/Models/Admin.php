<?php

namespace App\Models;

use App\Models\Notification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens ;

    protected $fillable = ['name', 'email', 'password', 'role' , 'permissions'];

    protected $hidden = ['password'];


     protected $casts = [
        'permissions' => 'array',
        'password'    => 'hashed',

    ];

     public function isSuper(): bool
    {
        return $this->role === 'super';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuper()) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    public function hasAnyPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
