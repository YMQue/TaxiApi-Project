<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverDocuments extends Model
{
    protected $fillable = [
        'driver_id',
        'type',
        'file_path',
        'status',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    
}
