<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function pricingRules()
{
    return $this->hasMany(CarTypePricingRule::class);
}

public function priceAt(?Carbon $at = null): array
{
    $at   = ($at ?? now())->copy()->timezone(config('app.pricing_timezone'));
    $time = $at->format('H:i:s');

    $rule = $this->pricingRules
        ->where('is_active', true)
        ->first(fn ($r) => $r->covers($time));

    return [
        'base_fare'         => (float) ($rule?->base_fare   ?? $this->base_fare   ?? 0),
        'per_km'            => (float) ($rule?->per_km      ?? $this->per_km      ?? 0),
        'per_minute'        => (float) ($rule?->per_minute  ?? $this->per_minute  ?? 0),
        'min_fare'          => (float) ($rule?->min_fare    ?? $this->min_fare    ?? 0),
        'pricing_rule_id'   => $rule?->id,
        'pricing_rule_name' => $rule?->name,
    ];
}
}
