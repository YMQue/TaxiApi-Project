<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_fare',
        'per_km_fare',
        'per_minute_fare',
        'image_url',
    ];

    protected $casts = [
        'base_fare' => 'float',
        'per_km_fare' => 'float',
        'per_minute_fare' => 'float',
    ];

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }
}
