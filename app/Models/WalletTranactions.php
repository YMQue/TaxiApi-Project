<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTranactions extends Model
{
     protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
    ];
 
    
 
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
 
    /** The ride / topup request / withdrawal request that caused this transaction. */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
 
  
}
