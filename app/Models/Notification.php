<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $fillable = [
        'notifiable_id' , 'notifiable_type' , 'type' , 'title' , 'body' , 'data' , 'read_at' , 'sent_at'
    ];

    public $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        ];

        public function notifiable()
    {
        return $this->morphTo();
    }
}
