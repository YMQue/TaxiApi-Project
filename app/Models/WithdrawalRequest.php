<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'processed_at',
    ];
 
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];
 
    public function user(): BelongsTo
    {
        return $this->belongsTo(user::class);
    }

    public function walletTransactions()
{
    return $this->morphMany(WalletTranactions::class, 'reference');
}
}
