<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
        protected $fillable = ['phone_number', 'code', 'expires_at', 'used_at'];
}
