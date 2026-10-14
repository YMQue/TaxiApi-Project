<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ride extends Model
{
    protected $fillable = [
        'rider_id',
        'driver_id',
        'car_type_id',
        'status',
        'pickup_lat',
        'pickup_lng',
        'pickup_address',
        'drop_lat',
        'drop_lng',
        'drop_address',
        'distance_km',
        'duration_minutes',
        'estimated_fare',
        'final_fare',
        'payment_method',
        'started_at',
        'ended_at',
        'cancelled_at',
        'cancel_reason',
        'cancel_by',
        
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'drop_lat' => 'decimal:7',
        'drop_lng' => 'decimal:7',
        'distance_km' => 'decimal:2',
        'estimated_fare' => 'decimal:2',
        'final_fare' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarType::class);
    }
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ride_status_log::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function locations(): HasOne
    {
         return $this->hasOne(RideLocation::class)->latestOfMany('recorded_at');
    }

    public function walletTransactions()
    {
        return $this->morphMany(WalletTranactions::class, 'reference');
    }
}
