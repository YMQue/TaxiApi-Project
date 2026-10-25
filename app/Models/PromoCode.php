<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
     use SoftDeletes;

    protected $fillable = [
        'code', 'type', 'value', 'min_ride_amount', 'max_discount',
        'usage_limit', 'user_limit_per_code', 'valid_from', 'valid_to', 'is_active',
    ];

    protected $casts = [
        'value'           => 'decimal:2',
        'min_ride_amount' => 'decimal:2',
        'max_discount'    => 'decimal:2',
        'valid_from'      => 'datetime',
        'valid_to'        => 'datetime',
        'is_active'       => 'boolean',
    ];

    // الكود دائماً بحروف كبيرة وبدون مسافات
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }
}
