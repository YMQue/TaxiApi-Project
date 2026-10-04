<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Repositories\Contracts\AdminRepositoryInterface;

class AdminRepository implements AdminRepositoryInterface
{
    public function findByEmail(string $email): ?Admin
    {
        return Admin::where('email', $email)->first();
    }
}