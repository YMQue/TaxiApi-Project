<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'driver_id',
        'amount',
        'bank_name',
        'account_number',
        'iban',
        'status',
        'admin_note',
        'wallet_transaction_id',
        'processed_at',
    ];
 
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];
 
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
 
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
