<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCodeUsage extends Model
{
    public $timestamps = false;

    protected $fillable = ['promo_code_id', 'user_id', 'ride_id', 'discount_amount', 'used_at'];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'used_at'         => 'datetime',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }
}
