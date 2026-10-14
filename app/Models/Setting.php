<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
      protected $fillable = [
        'commission_rate',
        'driver_search_radius_km',
        'ride_request_timeout_sec',
        'max_driver_search_attempts',
        'min_withdrawal_amount',
        'cancellation_fee',
    ];

    protected $casts = [
        'commission_rate'            => 'float',
        'driver_search_radius_km'    => 'float',
        'ride_request_timeout_sec'   => 'integer',
        'max_driver_search_attempts' => 'integer',
        'min_withdrawal_amount'      => 'float',
        'cancellation_fee'           => 'float',
    ];
}
