<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopupRequest extends Model
{
    protected $fillable = ['user_id', 'amount', 'method', 'payment_ref', 'status'];
 
   
 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

     public function walletTransactions()
{
    return $this->morphMany(WalletTranactions::class, 'reference');
}
}
