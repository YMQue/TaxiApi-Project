<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopupRequest extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'payment_ref',
        'status',
        'admin_note',
        'wallet_transaction_id',
        'processed_at',
    ];
 
    protected $casts = [
        'amount' => 'decimal:2',
    ];
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
