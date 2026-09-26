<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Driver;
use App\Models\Notification;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


#[Fillable([
    'name',
    'email',
    'phone_number',
    'fcm_token',
    'wallet_balance',
    'status',
    'avatar',
])]
#[Hidden(['remember_token', 'deleted_at',])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
    // ================= Relationships =================

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function TopupRequest()
    {
        return $this->hasMany(TopupRequest::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
    public function routeNotificationForFcm(): string|array|null
    {
        return $this->fcm_token;
    }

    public function Wallet()
    {
        return $this->hasOne(Wallet::class)->where('user_type', 'rider');
    }





    // -------helpers-----------

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDriver(): bool
    {
        return $this->driver()->exists();
    }

  
}
