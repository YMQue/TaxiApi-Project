<?php

namespace App\Models;

use App\Models\CarType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Driver extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'user_id',
        'car_type_id',
        'license_number',
        'plate_number',
        'is_online',
        'current_lat',
        'current_lng',
        'rate',
        'wallet_balance',
        'status',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'rate' => 'string',
    ];


    // ================= Relationships =================


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carType()
    {
        return $this->belongsTo(CarType::class);
    }

    public function driver_documents()
    {
        return $this->hasMany(DriverDocuments::class);
    }
    public function WithdrawalRequest()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class)->where('user_type', 'driver');
    }
    public function rides()
    {
        return $this->hasMany(Ride::class, 'driver_id');
    }
    public function rating()
    {
        return $this->hasMany(Rating::class, 'driver_id');
    }


  




    // ================= Accessors / Helpers =================

    public function isActive(): bool
    {
        return $this->user->status === 'active';
    }

    public function isOnline():bool
    {
        return $this->is_online === true;
    }

    public function isAvailable(): bool
    {
        return $this->is_online && $this->isActive();
    }

    
}
