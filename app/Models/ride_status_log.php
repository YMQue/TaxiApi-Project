<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ride_status_log extends Model
{
      public $timestamps = false;

    protected $fillable = [
        'ride_id', 'status', 'changed_by', 'changed_at', 'note',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}

