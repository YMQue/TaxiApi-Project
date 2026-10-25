<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarType extends Model
{
    protected $fillable = [
       'type',
        'icon',
        'base_fare',
        'per_km',
        'per_minute',
        'min_fare',
        'capacity',
    ];

    protected $casts = [
       'base_fare'  => 'float',
        'per_km'     => 'float',
        'per_minute' => 'float',
        'min_fare'   => 'float',
        'capacity'   => 'integer',
    ];
    

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }
}
