<?php

namespace App\Models;

use App\Models\Notification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'role', 'permissions'];

    protected $hidden = ['password'];

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
